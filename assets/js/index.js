const LicenseGenerator = {
    _enc: new TextEncoder(),

    init() {
        this.onSubmitFormEvent();
        this.numberValidationEvent();
        this.licenseUploadEvent();
    },

    onSubmitFormEvent() {
        let data = null;
        let initialIssuedDate = null;
        let companyName = null;

        const form = document.getElementById("generateLicenseForm")
        form.addEventListener("submit", (e) => {
            if (!form.checkValidity()) return;
            e.preventDefault();

            const formData = new FormData(e.target);
            initialIssuedDate = Date.now();
            companyName = formData.get("companyName");
            formData.append("licenseInitialIssuedDate", initialIssuedDate)

            data = Object.fromEntries(formData.entries());
            form.reset();
            this._showDownloadSecretKeyModal();
        })

        const secretkeyForm = document.getElementById("secretkeyForm");
        secretkeyForm.addEventListener("submit", (e) => {
            e.preventDefault();

            const secretKey = document.getElementById("secretkeyInput").value;
            if (!secretKey) return;

            this._encrypt(JSON.stringify(data), secretKey).then(({ savedCiphertext, iv, salt }) => {
                const dataToSave = {
                    data: btoa(String.fromCharCode(...savedCiphertext)),
                    iv: btoa(String.fromCharCode(...iv)),
                    salt: btoa(String.fromCharCode(...salt)),
                    "kdf": {
                        "name": "PBKDF2",
                        "iterations": 100000,
                        "hash": "SHA-256",
                        "keyLength": 256
                    }
                };
                this._downloadJSONLicenseFile(dataToSave, companyName, initialIssuedDate);
                data = initialIssuedDate = companyName = null;

                this._hideDownloadSecretKeyModal();

                const toastBootstrap = bootstrap.Toast.getOrCreateInstance(document.getElementById("liveToast"))
                toastBootstrap.show();
            });

        });
    },

    licenseUploadEvent() {
        let encrypted = null;

        const licenseUpload = document.getElementById("licenseUpload");

        licenseUpload.addEventListener("click", (e) => {
            licenseUpload.value = null; // Clear the file input value
        });

        licenseUpload.addEventListener("change", (e) => {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();

            reader.onload = (event) => {
                encrypted = JSON.parse(event.target.result);
                console.log(encrypted);
                if (encrypted.data && encrypted.iv && encrypted.salt) {
                    this._showUploadSecretKeyModal();
                } else {
                    console.error("Invalid license file format.");
                    return;
                }
                // const decryptedData = this._decrypt(data.data, data.iv, data.salt, secretKey);
                // console.log(decryptedData);
            };
            reader.readAsText(file);
        });

        const uploadSecretkeyForm = document.getElementById("uploadSecretKeyForm");
        uploadSecretkeyForm.addEventListener("submit", (e) => {
            e.preventDefault();
            const secretKeyInput = document.getElementById("uploadSecretKeyInput");
            const secretKey = secretKeyInput.value;
            if (!secretKey) return;

            this._decrypt(encrypted.data, encrypted.iv, encrypted.salt, secretKey).then((decryptedData) => {
                this._hideUploadSecretKeyModal();
                const toastMessage = document.getElementById("liveToast");
                const toastBody = toastMessage.querySelector(".toast-body");
                const toastHeader = toastMessage.querySelector(".toast-header-text");
                toastHeader.innerHTML = "Success";
                toastBody.innerHTML = "License file uploaded successfully.";
                toastMessage.classList.remove("text-bg-primary");
                toastMessage.classList.remove("text-bg-danger");
                toastMessage.classList.add("text-bg-success");

                const toastBootstrap = bootstrap.Toast.getOrCreateInstance(document.getElementById("liveToast"))
                toastBootstrap.show();

                for (const [key, value] of Object.entries(decryptedData)) {
                    const input = document.querySelector(`input[name="${key}"]`);
                    if (input) {
                        input.value = value;
                    }
                }
                console.log(decryptedData);
            }).catch((error) => {
                this._hideUploadSecretKeyModal();
                //update toast message to show error
                const toastMessage = document.getElementById("liveToast");
                const toastBody = toastMessage.querySelector(".toast-body");
                const toastHeader = toastMessage.querySelector(".toast-header-text");
                toastHeader.innerHTML = "Error";
                toastBody.innerHTML = "Invalid secret key. Please try again.";
                toastMessage.classList.remove("text-bg-primary");
                toastMessage.classList.remove("text-bg-success");
                toastMessage.classList.add("text-bg-danger");

                const toastBootstrap = bootstrap.Toast.getOrCreateInstance(document.getElementById("liveToast"))
                toastBootstrap.show();
            }).finally(() => {
                encrypted = null;
                secretKeyInput.value = null; // Clear the secret key input
            });
        });

    },


    _showDownloadSecretKeyModal() {
        const secretkeyModal =  bootstrap.Modal.getOrCreateInstance(document.getElementById('secretkeyModal'));
        secretkeyModal.show();
    },

    _hideDownloadSecretKeyModal() {
        const secretkeyModal =  bootstrap.Modal.getOrCreateInstance(document.getElementById('secretkeyModal'));
        secretkeyModal.hide();
    },

    _showUploadSecretKeyModal() {
        const uploadSecretKeyModal =  bootstrap.Modal.getOrCreateInstance(document.getElementById('uploadSecretKeyModal'));
        uploadSecretKeyModal.show();
    },

    _hideUploadSecretKeyModal() {
        const uploadSecretKeyModal =  bootstrap.Modal.getOrCreateInstance(document.getElementById('uploadSecretKeyModal'));
        uploadSecretKeyModal.hide();
    },

    _getKeyMaterial(password) {
        return crypto.subtle.importKey(
            "raw", this._enc.encode(password), { name: "PBKDF2" }, false, ["deriveKey"]
        );
    },

    async _deriveKey(secretKey, salt) {
        const keyMaterial = await this._getKeyMaterial(secretKey);
        return crypto.subtle.deriveKey(
            {
                name: "PBKDF2",
                salt,
                iterations: 100000,
                hash: "SHA-256"
            },
            keyMaterial,
            { name: "AES-GCM", length: 256 },
            false,
            ["encrypt", "decrypt"]
        );
    },

    async _encrypt(jsonData, secretKey) {
        const iv = crypto.getRandomValues(new Uint8Array(12)); // 12-byte IV
        const salt = crypto.getRandomValues(new Uint8Array(16)); // 16-byte salt
        const key = await this._deriveKey(secretKey, salt);
        const encrypted = await crypto.subtle.encrypt(
            { name: "AES-GCM", iv },
            key,
            this._enc.encode(jsonData)
        );
        const savedCiphertext = new Uint8Array(encrypted);
        return { savedCiphertext, iv, salt };
    },

    async _decrypt(encryptedData, iv, salt, secretKey) {
        const key = await this._deriveKey(secretKey, new Uint8Array(atob(salt).split("").map(c => c.charCodeAt(0))));
        const decrypted = await crypto.subtle.decrypt(
            { name: "AES-GCM", iv: new Uint8Array(atob(iv).split("").map(c => c.charCodeAt(0))) },
            key,
            new Uint8Array(atob(encryptedData).split("").map(c => c.charCodeAt(0)))
        );
        return JSON.parse(new TextDecoder().decode(decrypted));
    },

    _downloadJSONLicenseFile(data, companyName, initialIssuedDate) {
        const fileName = companyName + "_" + initialIssuedDate + "_" + "License.json";
        const blob = new Blob([JSON.stringify(data)], { type: "application/json" });
        const url = URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = url;
        a.download = fileName;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    },

    numberValidationEvent() {
        const numberInputs = document.querySelectorAll('#machineLicenseContainer input[type="number"]');
        numberInputs.forEach((input) => {
            input.addEventListener("input", (event) => {
                let value = event.target.value;
                event.target.value = value.replace(/[^0-9]/g, ''); // Allow only positive numbers
            });
        });
    },


}

LicenseGenerator.init();