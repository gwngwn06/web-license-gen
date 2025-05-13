const LicenseGenerator = {
    _enc: new TextEncoder(),
    _secretkey: "mySecretKey",
    _annualMaintenanceExpDate: null,

    init() {
        this.onSubmitFormEvent();
        this.onLicenseUploadEvent();
        this.numberValidationEvent();
    },

    onSubmitFormEvent() {
        let data = null;
        let companyName = null;
        const form = document.getElementById("generateLicenseForm")

        form.addEventListener("submit", (e) => {

            if (!form.checkValidity()) return;
            e.preventDefault();

            const formData = new FormData(e.target);
            companyName = formData.get("companyName");
            // formData.append("annualMaintenanceExpDate", this._annualMaintenanceExpDate);

            data = Object.fromEntries(formData.entries());
            console.log(JSON.stringify(data));


            fetch("./licenses/license.php", {
                method: "POST",
                body: formData,
            })
                .then((response) => {
                    if (!response.ok) {
                        return response.json().then(err => {
                            throw new Error(err.error || 'Unknown error');
                        });
                    }
                    return response.json();
                })
                .then((result) => {
                    // NOTE: PREFER BACKEND ENCRYPTION / DECRYPTION
                    console.log("License file saved successfully:", result);
                    const license = result.license;
                    data["licenseId"] = license.id;
                    data["licenseCreatedAt"] = license.createdAt;
                    data['licenseUpdatedAt'] = license.updatedAt;
                    data['mdcPermanentCount'] = license.mdc;
                    data['dncPermanentCount'] = license.dnc;
                    data['hmiPermanentCount'] = license.hmi;
                    delete data["userId"];


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
                        console.log("download unencryptedData: ", unencryptedData);
                        console.log("download encryptedData:", encryptedData);
                        this._downloadJSONLicenseFile(unencryptedData, "UNENCRYPTED_" + companyName, license.createdAt);
                        this._downloadJSONLicenseFile(encryptedData, companyName, license.createdAt);
                        form.reset();

                        if (license.isUpdated) {
                            this._showToastMessage("Your license file has been updated.", "info");
                        } else {
                            this._showToastMessage("Your license has been generated.", "info");
                        }

                        document.getElementById("fileDownloadText").textContent = "Generate & Download License File";
                        data = companyName = null;
                    }).catch((error) => {
                        this._showToastMessage("Unable to encrypt your file", "error");
                    });
                })
                .catch((error) => {
                    console.error("Error saving license file:", error);
                    this._showToastMessage("Error saving license file", "error");
                });

        })

    },

    onLicenseUploadEvent() {
        let encrypted = null;
        const licenseUpload = document.getElementById("licenseUpload");

        licenseUpload.addEventListener("click", (e) => {
            licenseUpload.value = null;
        });

        licenseUpload.addEventListener("change", (e) => {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();

            reader.onload = (event) => {
                encrypted = JSON.parse(event.target.result);
                if (encrypted.data && encrypted.iv && encrypted.salt) {
                    this._decrypt(encrypted.data, encrypted.iv, encrypted.salt, this._secretkey).then((decryptedData) => {
                        // NOTE: check if exist in db?
                        console.log("decryptedData: ", decryptedData);
                        if (decryptedData.licenseId == null || decryptedData.codeVerifier == null 
                            || decryptedData.resellerName == null || decryptedData.resellerCode == null 
                            || decryptedData.companyName == null || decryptedData.customerName == null 
                            || decryptedData.customerEmail == null || decryptedData.customerAddress == null
                            || decryptedData.customerContactNumber == null 
                            || decryptedData.mdcTrialDays == null || decryptedData.dncTrialCount == null
                            || decryptedData.dncTrialDays == null || decryptedData.hmiTrialCount == null
                            || decryptedData.hmiTrialDays == null || decryptedData.licenseCreatedAt == null
                            || decryptedData.licenseUpdatedAt == null || decryptedData.mdcPermanentCount == null
                            || decryptedData.dncPermanentCount == null || decryptedData.hmiPermanentCount == null ) {

                            throw new Error("Missing file data");
                        }
                        this._showToastMessage(null, "success");

                        const utypeDiv = document.getElementById("utype");
                        const utype = utypeDiv.getAttribute('data-utype');
                        if (decryptedData['mdcPermanentCount'] != 0 || decryptedData['dncPermanentCount'] != 0 || decryptedData['hmiPermanentCount'] != 0) {
                            const mdcPermanentCount = document.getElementById('mdcPermanentCount');
                            const dncPermanentCount = document.getElementById('dncPermanentCount');
                            const hmiPermanentCount = document.getElementById('hmiPermanentCount');
                            document.querySelectorAll('.permanent-license').forEach(function (element) {
                                element.classList.remove('d-none');
                            });
                            if (utype == "0") {
                                mdcPermanentCount.disabled = true;
                                dncPermanentCount.disabled = true;
                                hmiPermanentCount.disabled = true;
                            } else {
                                mdcPermanentCount.disabled = false;
                                dncPermanentCount.disabled = false;
                                hmiPermanentCount.disabled = false;
                            }
                        } else {
                            document.querySelectorAll('.permanent-license').forEach(function (element) {
                                element.classList.add('d-none');
                            });
                        }

                        const generateLicenseForm = document.getElementById("generateLicenseForm");
                        for (const [key, value] of Object.entries(decryptedData)) {
                            const input = generateLicenseForm.querySelector(`input[name="${key}"]`);
                            if (input) {
                                input.value = value;
                            }
                        }

                        document.getElementById("fileDownloadText").textContent = "Update License File";
                    }).catch((error) => {
                        // console.log(error);
                        this._showToastMessage("Invalid license file format.", "error");
                    }).finally(() => {
                        encrypted = null;
                    });
                } else {
                    this._showToastMessage("Invalid license file format.", "error");
                    return;
                }
            };
            reader.readAsText(file);
        });

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


const SearchLicense = {
    _params: {
        selectedColumnHeader: "reseller",
        selectedOrder: "asc",

        search: "",

        currentPage: 1,
        totalPage: 0,
    },

    init() {
        document.getElementById('searchModal').addEventListener('shown.bs.modal', () => {
            document.getElementById("searchLicenseInput").focus();
            this.searchLicenses(this._params.search ?? '');
        });

        this.onSearchLicenseEvent();
        this.onPaginationEvent();
        this.onSortTableHeader();
    },

    onSortTableHeader() {
        let selectedColumnHeader = "reseller";
        let selectedOrder = "asc";

        document.querySelectorAll('#licenseTable thead th').forEach((th, index) => {
            th.addEventListener('click', (e) => {

                const order = e.target.getAttribute("data-order");
                if (order !== null && order === "asc") {
                    e.target.setAttribute("data-order", "desc");
                    selectedOrder = "desc"
                } else if (order === "desc") {
                    e.target.setAttribute("data-order", "asc");
                    selectedOrder = "asc";
                }
                selectedColumnHeader = e.target.getAttribute("data-sort");

                const img = e.target.querySelector('.sorting-order');
                if (img) {
                    img.src = selectedOrder === "asc"
                        ? './assets/icons/caret-up-fill.svg'
                        : './assets/icons/caret-down-fill.svg';
                }

                this._params.selectedColumnHeader = selectedColumnHeader;
                this._params.selectedOrder = selectedOrder;

                this.searchLicenses(this._params.search ?? '');
            });
        });

    },

    onPaginationEvent() {
        const prevBtn = document.getElementById("paginationPrevBtn");
        const nextBtn = document.getElementById("paginationNextBtn");

        if (this._params.currentPage >= this._params.totalPage) {
            nextBtn.setAttribute("disabled", "true");
        }
        nextBtn.addEventListener("click", () => {
            if (this._params.currentPage < this._params.totalPage) {
                this._params.currentPage += 1;
                this.searchLicenses(this._params.search ?? '');
            }
            if (this._params.currentPage == this._params.totalPage) {
                nextBtn.setAttribute("disabled", "true");
            }
            if (this._params.currentPage > 1) {
                prevBtn.removeAttribute("disabled");
            }
        });

        if (this._params.currentPage == 1) {
            prevBtn.setAttribute("disabled", "true");
        }
        prevBtn.addEventListener("click", () => {
            if (this._params.currentPage > 1) {
                this._params.currentPage -= 1;
                this.searchLicenses(this._params.search ?? '');
            }
            if (this._params.currentPage <= 1) {
                prevBtn.setAttribute("disabled", "true");
            }
            if (this._params.currentPage < this._params.totalPage) {
                nextBtn.removeAttribute("disabled");
            }
        });
    },


    onSearchLicenseEvent() {
        const searchInput = document.getElementById("searchLicenseInput");

        const searchData = (event) => {
            this.searchLicenses(event.target.value);
        }

        const debounce = (callback, waitTime) => {
            let timer;
            return (...args) => {
                clearTimeout(timer);
                timer = setTimeout(() => {
                    callback(...args);
                }, waitTime);
            };
        }

        const debounceHandler = debounce(searchData, 500);
        searchInput.addEventListener("input", debounceHandler);
    },

    searchLicenses(value) {
        const dateFormat = {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit'
        };

        function snakeToCamel(str) {
            return str.replace(/_([a-z])/g, (_, letter) => letter.toUpperCase());
        }

        this._params.search = value;
        const params = new URLSearchParams(this._params).toString();

        fetch(`./licenses/license.php?${params}`)
            .then(res => res.json())
            .then(data => {
                console.log("Search results:", data);

                this._params.totalPage = data.metadata.totalPage;
                this._params.currentPage = data.metadata.currentPage;
                if (this._params.currentPage < this._params.totalPage) {
                    document.getElementById("paginationNextBtn").removeAttribute("disabled");
                }
                document.getElementById("paginationPageCount").textContent = `${this._params.currentPage} / ${this._params.totalPage}`;

                const tableBody = document.getElementById("searchResultsTableBody");
                tableBody.innerHTML = "";
                if (data.result.length === 0) {
                    document.getElementById("noLicenseMessage").hidden = false;
                    return;
                }
                document.getElementById("noLicenseMessage").hidden = true;

                data.result.forEach(item => {
                    const row = document.createElement("tr");
                    const viewButtontd = document.createElement("td");
                    const viewButton = document.createElement("button");
                    const generateLicenseForm = document.getElementById("generateLicenseForm");
                    const currentLicenseUserId = generateLicenseForm.querySelector('input[name="userId"]').value;
                    const cidDiv = document.getElementById("cid");
                    // const cid = cidDiv.getAttribute('data-cid');
                    const utypeDiv = document.getElementById("utype");
                    const utype = utypeDiv.getAttribute('data-utype');

                    viewButton.classList.add("btn", "btn-outline-primary", "rounded-3", "btn-sm");
                    viewButton.setAttribute("data-bs-dismiss", "modal");
                    viewButton.innerText = "View";
                    viewButtontd.appendChild(viewButton);

                    const licenseCreatedAt = new Date(item['license_created_at'].replace(' ', 'T')).toLocaleString('en-US', dateFormat);
                    const licenseUpdatedAt = new Date(item['license_updated_at'].replace(' ', 'T')).toLocaleString('en-US', dateFormat);

                    row.innerHTML = `
                        <td class="${currentLicenseUserId == item['user_id'] ? 'fw-medium' : ''}"><small>${item['reseller_name'].toUpperCase()}</small></td>
                        <td><small>${item['company_name'].toUpperCase()}</small></td>
                        <td><small>${licenseCreatedAt}</small></td>
                        <td><small>${licenseUpdatedAt}</small></td>
                        `;
                    row.appendChild(viewButtontd);

                    viewButton.addEventListener("click", () => {

                        if (item['mdc_permanent_count'] != 0 || item['dnc_permanent_count'] != 0 || item['hmi_permanent_count'] != 0) {
                            const mdcPermanentCount = document.getElementById('mdcPermanentCount');
                            const dncPermanentCount = document.getElementById('dncPermanentCount');
                            const hmiPermanentCount = document.getElementById('hmiPermanentCount');
                            document.querySelectorAll('.permanent-license').forEach(function (element) {
                                element.classList.remove('d-none');
                            });
                            if (utype == "0") {
                                mdcPermanentCount.disabled = true;
                                dncPermanentCount.disabled = true;
                                hmiPermanentCount.disabled = true;
                            } else {
                                mdcPermanentCount.disabled = false;
                                dncPermanentCount.disabled = false;
                                hmiPermanentCount.disabled = false;
                            }
                        } else if (utype == '0') {
                            document.querySelectorAll('.permanent-license').forEach(function (element) {
                                element.classList.add('d-none');
                            });
                        }

                        for (const [key, value] of Object.entries(item)) {
                            let camelCaseKey = snakeToCamel(key);
                            // if (camelCaseKey == "userId") continue;
                            if (camelCaseKey == "id") camelCaseKey = "licenseId";

                            const input = generateLicenseForm.querySelector(`input[name="${camelCaseKey}"]`);
                            if (input) {
                                input.value = value;

                                document.getElementById("fileDownloadText").textContent = "Update License File";
                            }
                        }
                    });
                    tableBody.appendChild(row);
                });
            })
            .catch(err => {
                console.error("Error fetching search results:", err);
            });
    }
}

SearchLicense.init();
LicenseGenerator.init();