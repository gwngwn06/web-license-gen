const LicenseGenerator = {
    _enc: new TextEncoder(),
    _secretkey: "mySecretKey",
    _initialIssuedDate: null,
    _annualMaintenanceExpDate: null,

    init() {
        this.onSubmitFormEvent();
        this.licenseUploadEvent();
        this.numberValidationEvent();
    },

    onSubmitFormEvent() {
        let data = null;
        let companyName = null;
        let isLicenseFileUpdated = true;
        const form = document.getElementById("generateLicenseForm")

        form.addEventListener("submit", (e) => {
            if (!form.checkValidity()) return;
            e.preventDefault();

            const formData = new FormData(e.target);
            companyName = formData.get("companyName");
            if (this._initialIssuedDate === null || this._initialIssuedDate === "") {
                isLicenseFileUpdated = false;
                this._initialIssuedDate = Date.now();
            }
            // formData.append("lastLicenseUpdateDate", Date.now());
            // formData.append("licenseInitialIssuedDate", this._initialIssuedDate);
            formData.append("annualMaintenanceExpDate", this._annualMaintenanceExpDate);

            data = Object.fromEntries(formData.entries());
            // this._showDownloadSecretKeyModal();
            const unencryptedData = {
                data,
                iv: null,
                salt: null,
                "kdf": {
                    "name": "PBKDF2",
                    "iterations": 100000,
                    "hash": "SHA-256",
                    "keyLength": 256
                }
            }

            fetch("./licenses/license.php", {
                method: "POST",
                body: formData,
            })
                .then((response) => response.json())
                .then((result) => {
                    console.log("License file saved successfully:", result);
                    this._initialIssuedDate = result.created_at;
                    data["licenseId"] = result.id;
                    this._encrypt(JSON.stringify(data), this._secretkey).then(({ savedCiphertext, iv, salt }) => {
                        const encryptedData = {
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
                        console.log("download unencryptedData: ", unencryptedData);
                        console.log("download encryptedData:", encryptedData);
                        this._downloadJSONLicenseFile(unencryptedData, "UNENCRYPTED_" + companyName, this._initialIssuedDate);
                        this._downloadJSONLicenseFile(encryptedData, companyName, this._initialIssuedDate);
                        form.reset();

                        // this._hideDownloadSecretKeyModal();
                        if (isLicenseFileUpdated) {
                            this._showToastMessage("Your license file has been updated.", "info");
                        } else {
                            this._showToastMessage(null, "info");
                        }
                        document.getElementById("fileDownloadText").textContent = "Generate & Download License File";
                        data = this._initialIssuedDate = companyName = null;
                        isLicenseFileUpdated = true;
                    }).catch((error) => {
                        this._showToastMessage("Unable to encrypt your file", "error");
                    });
                })
                .catch((error) => {
                    console.error("Error saving license file:", error);
                });

        })

        // const secretkeyForm = document.getElementById("secretkeyForm");
        // secretkeyForm.addEventListener("submit", (e) => {
        //     e.preventDefault();

        //     const secretKey = document.getElementById("secretkeyInput").value;
        //     if (!secretKey) return;

        //     this._encrypt(JSON.stringify(data), secretKey).then(({ savedCiphertext, iv, salt }) => {
        //         const dataToSave = {
        //             data: btoa(String.fromCharCode(...savedCiphertext)),
        //             iv: btoa(String.fromCharCode(...iv)),
        //             salt: btoa(String.fromCharCode(...salt)),
        //             "kdf": {
        //                 "name": "PBKDF2",
        //                 "iterations": 100000,
        //                 "hash": "SHA-256",
        //                 "keyLength": 256
        //             }
        //         };
        //         this._downloadJSONLicenseFile(dataToSave, companyName, initialIssuedDate);
        //         form.reset();
        //         data = initialIssuedDate = companyName = null;

        //         this._hideDownloadSecretKeyModal();
        //         this._showToastMessage(null, "info");
        //     });

        // });
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
                if (encrypted.data && encrypted.iv && encrypted.salt) {
                    // this._showUploadSecretKeyModal();
                    this._decrypt(encrypted.data, encrypted.iv, encrypted.salt, this._secretkey).then((decryptedData) => {
                        console.log("decryptedData: ", decryptedData);
                        this._initialIssuedDate = decryptedData.licenseInitialIssuedDate;
                        // this._hideUploadSecretKeyModal();
                        this._showToastMessage(null, "success");

                        const generateLicenseForm = document.getElementById("generateLicenseForm");
                        for (const [key, value] of Object.entries(decryptedData)) {
                            const input = generateLicenseForm.querySelector(`input[name="${key}"]`);
                            if (input) {
                                input.value = value;
                            }
                        }

                        document.getElementById("fileDownloadText").textContent = "Update License File";
                    }).catch((error) => {
                        // this._hideUploadSecretKeyModal();
                        console.log(error);
                        this._showToastMessage(null, "error");
                    }).finally(() => {
                        encrypted = null;
                        // secretKeyInput.value = null;
                    });
                } else {
                    // console.error("Invalid license file format.");
                    this._showToastMessage("Invalid license file format.", "error");
                    return;
                }
            };
            reader.readAsText(file);
        });

        // const uploadSecretkeyForm = document.getElementById("uploadSecretKeyForm");
        // uploadSecretkeyForm.addEventListener("submit", (e) => {
        //     e.preventDefault();
        //     const secretKeyInput = document.getElementById("uploadSecretKeyInput");
        //     const secretKey = secretKeyInput.value;
        //     if (!secretKey) return;
        // });

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

    // _showDownloadSecretKeyModal() {
    //     const secretkeyModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('secretkeyModal'));
    //     secretkeyModal.show();
    // },

    // _hideDownloadSecretKeyModal() {
    //     const secretkeyModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('secretkeyModal'));
    //     secretkeyModal.hide();
    // },

    // _showUploadSecretKeyModal() {
    //     const uploadSecretKeyModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('uploadSecretKeyModal'));
    //     uploadSecretKeyModal.show();
    // },

    // _hideUploadSecretKeyModal() {
    //     const uploadSecretKeyModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('uploadSecretKeyModal'));
    //     uploadSecretKeyModal.hide();
    // },

    _showToastMessage(message, type) {
        const toastMessage = document.getElementById("liveToast");
        const toastBody = toastMessage.querySelector(".toast-body");
        const toastHeader = toastMessage.querySelector(".toast-header-text");
        if (type === "success") {
            toastHeader.innerHTML = message ?? "Success";
            toastBody.innerHTML = "License file uploaded successfully.";
            toastMessage.classList.remove("text-bg-primary");
            toastMessage.classList.remove("text-bg-danger");
            toastMessage.classList.add("text-bg-success");
        } else if (type === "info") {
            toastHeader.innerHTML = "License Generated";
            toastBody.innerHTML = message ?? "Your license has been generated.";
            toastMessage.classList.remove("text-bg-success");
            toastMessage.classList.remove("text-bg-danger");
            toastMessage.classList.add("text-bg-primary");
        } else if (type === "error") {
            toastHeader.innerHTML = "Error";
            toastBody.innerHTML = message ?? "Invalid secret key. Please try again.";
            toastMessage.classList.remove("text-bg-primary");
            toastMessage.classList.remove("text-bg-success");
            toastMessage.classList.add("text-bg-danger");

        }
        const toastBootstrap = bootstrap.Toast.getOrCreateInstance(toastMessage);
        toastBootstrap.show();
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