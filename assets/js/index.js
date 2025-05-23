const GlobalParams = {
    resellerId: -1,
    selectedLicense: {}
}

const GlobalFunctions = {
    showLicenseExpirationLabel(createdDate) {
        const licenseExpirationLabel = document.getElementById("licenseExpirationLabel");
        licenseExpirationLabel.classList.remove("d-none");

        const createdAtDate = new Date(createdDate);
        const softwareMaintenanceExpirationDate = new Date(createdAtDate);
        softwareMaintenanceExpirationDate.setFullYear(createdAtDate.getFullYear() + 1)
        const formattedCreatedAtDate = createdAtDate.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
        const formattedMaintenanceExpirationDate = softwareMaintenanceExpirationDate.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });

        document.getElementById("licenseCreatedAt").textContent = "License Generated Date:\t" + formattedCreatedAtDate;
        document.getElementById("softwareMaintenanceExpiratedDate").textContent = "Software Maintenance Expiration Date:\t" + formattedMaintenanceExpirationDate;
    },

    hideLicenseExpirationLabel() {
        const licenseExpirationLabel = document.getElementById("licenseExpirationLabel");
        licenseExpirationLabel.classList.add("d-none");
    },

    showLicenseTags(fromDb = false) {
        if (fromDb) {
            const licenseTagInfo = document.getElementById("licenseTagInfo");
            licenseTagInfo.classList.add("d-none");

            const licenseInfos = document.querySelectorAll(".license-info");
            licenseInfos.forEach((span) => {
                span.classList.add("d-none");
            });

            const trialDaysInfos = document.querySelectorAll(".trial-days-info");
            trialDaysInfos.forEach((elem) => {
                elem.classList.remove("d-none");
            })
        } else {
            const licenseTagInfo = document.getElementById("licenseTagInfo");
            licenseTagInfo.classList.remove("d-none");

            const licenseInfos = document.querySelectorAll(".license-info");
            licenseInfos.forEach((span) => {
                span.classList.remove("d-none");
            });
        }
    },

    hideLicenseTags() {
        const licenseTagInfo = document.getElementById("licenseTagInfo");
        licenseTagInfo.classList.add("d-none");

        const licenseInfos = document.querySelectorAll(".license-info");
        licenseInfos.forEach((span) => {
            span.classList.add("d-none");
        });
    },

    togglePermanentLicenseField(mdcPermanentCount, dncPermanentCount, hmiPermanentCount) {
        const utypeDiv = document.getElementById("utype");
        const utype = utypeDiv.getAttribute('data-utype');
        if ((mdcPermanentCount != 0 || dncPermanentCount != 0 || hmiPermanentCount != 0) || utype == "1") {
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
    },

    updateTrialDaysInfo(input, dateLicenseUsed, fallbackValue) {
        const span = input.nextElementSibling;
        const remainingDays = span.querySelector(".remaining-days");
        if (dateLicenseUsed != null && !isNaN(new Date(dateLicenseUsed).getTime())) {
            const diffTime = Math.abs(Date.now() - new Date(dateLicenseUsed));
            const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
            const days = Math.max(fallbackValue - diffDays, 0);

            remainingDays.innerHTML = `<small>${days}</small>`;
        } else {
            remainingDays.innerHTML = `<small>${fallbackValue}</small>`;
        }
    }
}

const LicenseGenerator = {
    _enc: new TextEncoder(),
    _secretkey: "mySecretKey",
    _annualMaintenanceExpDate: null,

    init() {
        this.onSubmitFormEvent();
        this.onCancelSubmitFormEvent();
        this.onUndoClickEvent();
        this.onLicenseUploadEvent();
        this.numberValidationEvent();
    },

    onUndoClickEvent() {
        const undoFormBtn = document.getElementById("undoFormBtn");
        undoFormBtn.addEventListener("click", () => {
            document.getElementById("cancelFormBtn").disabled = false;
            document.getElementById("fileDownloadText").textContent = "Update License File";
            // put values to input fields
            const generateLicenseForm = document.getElementById("generateLicenseForm");
            for (const [key, value] of Object.entries(GlobalParams.selectedLicense)) {
                const input = generateLicenseForm.querySelector(`input[name="${key}"]`);
                if (input) {
                    input.value = value;
                }
            }
            GlobalFunctions.togglePermanentLicenseField(
                GlobalParams.selectedLicense.mdcPermanentCount,
                GlobalParams.selectedLicense.dncPermanentCount,
                GlobalParams.selectedLicense.hmiPermanentCount);
            GlobalFunctions.showLicenseTags();
            GlobalFunctions.showLicenseExpirationLabel(GlobalParams.selectedLicense.licenseCreatedAt)
        });
    },

    onCancelSubmitFormEvent() {
        const form = document.getElementById("generateLicenseForm")
        const cancelFormBtn = document.getElementById("cancelFormBtn");

        const checkForm = () => {
            if (!this.isFormEmpty(form)) {
                cancelFormBtn.disabled = false;
            } else {
                cancelFormBtn.disabled = true;
            }
        };

        [...form.elements].forEach((el) => {
            if (
                el.tagName !== "BUTTON" &&
                el.type !== "submit" &&
                el.type !== "hidden" &&
                !el.disabled
            ) {
                el.addEventListener("input", checkForm);
            }
        });

        cancelFormBtn.addEventListener("click", () => {
            form.reset();
            document.getElementById("fileDownloadText").textContent = "Generate & Download License File";

            // make sure we are using the current user id when we reset
            this.resetToCurrentUserId();

            GlobalFunctions.hideLicenseExpirationLabel();
            GlobalFunctions.hideLicenseTags();
            GlobalFunctions.togglePermanentLicenseField(0, 0, 0);
            cancelFormBtn.disabled = true;
        });
    },

    resetToCurrentUserId() {
        const cid = document.getElementById("cid");
        const cidData = cid.getAttribute("data-cid");
        document.getElementById("userId").value = cidData;
        document.getElementById("licenseId").value = "";
        document.getElementById("dateLicenseUsed").value = "";
        document.getElementById("annualMaintenanceExpirationDate").value = "";
    },

    isFormEmpty(form) {
        const elements = form.elements;
        const licensesNames =
        {
            "mdcPermanentCount": "0", "mdcTrialCount": "0", "mdcTrialDays": "40",
            "dncPermanentCount": "0", "dncTrialCount": "0", "dncTrialDays": "40",
            "hmiPermanentCount": "0", "hmiTrialCount": "0", "hmiTrialDays": "40"
        };

        for (let i = 0; i < elements.length; i++) {
            const el = elements[i];

            if (
                el.disabled ||
                el.type === 'button' ||
                el.type === 'submit' ||
                el.type === 'hidden'
            ) continue;

            if (licensesNames[el.name] && (licensesNames[el.name] != el.value)) {
                return false;
            } else if (el.value && el.value.trim() !== "" && !licensesNames[el.name]) {
                return false;
            }
        }

        return true; // All relevant fields are empty
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
            // console.log(JSON.stringify(data));

            fetch("./licenses/license.php", {
                method: "POST",
                body: formData,
            })
                .then((response) => {
                    if (!response.ok) {
                        return response.json().then(err => {
                            console.log(JSON.stringify(err));
                            throw new Error(err.message || 'Unknown error');
                        });
                    }
                    return response.json();
                })
                .then((result) => {
                    // NOTE: PREFER BACKEND ENCRYPTION / DECRYPTION
                    // console.log("License file saved successfully:", result);
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

                        };
                        // const unencryptedData = {
                        //     data,
                        //     iv: null,
                        //     salt: null,
                        // }
                        // console.log("download unencryptedData: ", unencryptedData);
                        // console.log("download encryptedData:", encryptedData);
                        // this._downloadJSONLicenseFile(unencryptedData, "UNENCRYPTED_" + companyName, license.createdAt);
                        this._downloadJSONLicenseFile(encryptedData, companyName, license.createdAt);


                        if (license.isUpdated) {
                            this._showToastMessage("Your license file has been updated.", "info");
                        } else {
                            this._showToastMessage("Your license has been generated.", "info");
                        }

                        form.reset();
                        GlobalFunctions.hideLicenseTags();
                        this.resetToCurrentUserId();
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
                    this._decrypt(encrypted.data, encrypted.iv, encrypted.salt, this._secretkey).then(async (decryptedData) => {
                        console.log("decryptedData: ", decryptedData);
                        if (
                            decryptedData.codeVerifier == null
                            || decryptedData.resellerFirstName == null || decryptedData.resellerLastName == null
                            || decryptedData.resellerCode == null
                            || decryptedData.companyName == null
                            || decryptedData.customerFirstName == null || decryptedData.customerLastName == null
                            || decryptedData.customerEmail == null || decryptedData.customerAddress == null
                            || decryptedData.customerContactNumber == null
                            || decryptedData.mdcTrialDays == null || decryptedData.dncTrialCount == null
                            || decryptedData.mdcTrialCount == null
                            || decryptedData.dncTrialDays == null || decryptedData.hmiTrialCount == null
                            || decryptedData.hmiTrialDays == null || decryptedData.licenseCreatedAt == null
                            || decryptedData.licenseUpdatedAt == null || decryptedData.mdcPermanentCount == null
                            || decryptedData.dncPermanentCount == null || decryptedData.hmiPermanentCount == null
                        ) {
                            throw new Error("Missing file data");
                        }

                        if ((decryptedData.licenseId == null || decryptedData.licenseId === undefined || decryptedData.licenseId === '')
                            &&
                            (decryptedData.dateLicenseUsed === undefined || decryptedData.dateLicenseUsed === null ||
                                decryptedData.dateLicenseUsed == '' || isNaN(new Date(decryptedData.dateLicenseUsed).getTime()))) {
                            throw new Error("Missing file data");
                        }

                        if (decryptedData.licenseId) {
                            // console.log("Existing license");
                            try {
                                const params = new URLSearchParams({ id: decryptedData.licenseId }).toString();
                                const resp = await fetch(`./licenses/license.php?${params}`)
                                const result = await resp.json();
                                if (result.error) throw new Error(result.error)

                                document.getElementById("userId").value = result.userId;
                                document.getElementById("cancelFormBtn").disabled = false;
                                document.getElementById("undoFormBtn").disabled = false;
                                GlobalParams.selectedLicense = { ...decryptedData, userId: result.userId };

                            } catch (err) {
                                this._showToastMessage(err.message, "error")
                                return;
                            }
                        } else {
                            // New file generated from the desktop app
                            console.log("Newly generated license from desktop app");
                        }

                        // show/hide and enable/disable permanent license field base on user type
                        GlobalFunctions.togglePermanentLicenseField(
                            decryptedData['mdcPermanentCount'],
                            decryptedData['dncPermanentCount'],
                            decryptedData['hmiPermanentCount']
                        );


                        // put values to input fields
                        const generateLicenseForm = document.getElementById("generateLicenseForm");
                        for (const [key, value] of Object.entries(decryptedData)) {
                            const input = generateLicenseForm.querySelector(`input[name="${key}"]`);
                            if (input) {
                                input.value = value;

                                if (input.name.includes("PermanentCount") || input.name.includes("TrialCount")) {
                                    const span = input.nextElementSibling;
                                    const availableLicense = span.querySelector(".available-license");
                                    const inUseLicense = span.querySelector(".in-use-license");
                                    const availableLicenseText = input.name.replace("Count", "Available");
                                    const inUsedLicenseText = input.name.replace("Count", "InUsed");

                                    availableLicense.innerHTML = `<small>${decryptedData[availableLicenseText] ?? value}</small>`;
                                    inUseLicense.innerHTML = `<small>${decryptedData[inUsedLicenseText] ?? 0}</small>`

                                } else if (input.name.includes("TrialDays")) {
                                    GlobalFunctions.updateTrialDaysInfo(input, decryptedData.dateLicenseUsed, value);
                                }
                            }
                        }

                        GlobalFunctions.showLicenseExpirationLabel(decryptedData.licenseCreatedAt);
                        GlobalFunctions.showLicenseTags();
                        this._showToastMessage(null, "success");
                        document.getElementById("fileDownloadText").textContent = "Update License File";

                    }).catch((error) => {
                        console.log(error);
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
        const formattedInitialIssuedDate = initialIssuedDate.replace(/[- ]/g, "_");
        const formattedCompanyName = companyName.replace(" ", "_");
        const fileName = formattedCompanyName + "_" + formattedInitialIssuedDate + "_" + "License.dat";
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
            toastBody.innerHTML = "License file was imported successfully.";
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
                // console.log("Search results:", data);

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
                        <td class="${currentLicenseUserId == item['user_id'] ? 'fw-medium' : ''}"><small>${item['reseller_first_name'] + " " + item['reseller_last_name']}</small></td>
                        <td><small>${item['company_name'].toUpperCase()}</small></td>
                        <td><small>${licenseCreatedAt}</small></td>
                        <td><small>${licenseUpdatedAt}</small></td>
                        `;
                    row.appendChild(viewButtontd);

                    viewButton.addEventListener("click", () => {

                        GlobalFunctions.togglePermanentLicenseField(
                            item['mdc_permanent_count'],
                            item['dnc_permanent_count'],
                            item['hmi_permanent_count']
                        );

                        console.log('item', item);
                        GlobalParams.selectedLicense = {};
                        for (const [key, value] of Object.entries(item)) {
                            let camelCaseKey = snakeToCamel(key);
                            // if (camelCaseKey == "userId") continue;
                            if (camelCaseKey == "id") camelCaseKey = "licenseId";
                            if (camelCaseKey == "serviceLicenseUpdatedAt") camelCaseKey = "dateLicenseUsed";

                            GlobalParams.selectedLicense[camelCaseKey] = value;

                            const input = generateLicenseForm.querySelector(`input[name="${camelCaseKey}"]`);
                            if (input) {
                                input.value = value;
                                document.getElementById("fileDownloadText").textContent = "Update License File";

                                if (input.name.includes("TrialDays")) {
                                    GlobalFunctions.updateTrialDaysInfo(input, item["service_license_updated_at"], value);
                                }
                            }
                        }
                        // console.log(GlobalParams.selectedLicense);
                        GlobalParams.resellerId = item.reseller_id;
                        document.getElementById("cancelFormBtn").disabled = false;
                        document.getElementById("undoFormBtn").disabled = false;
                        GlobalFunctions.showLicenseTags(true);
                        GlobalFunctions.showLicenseExpirationLabel(item['license_created_at']);
                    });


                    tableBody.appendChild(row);
                });
            })
            .catch(err => {
                console.error("Error fetching search results:", err);
            });
    }
}



const UserProfile = {
    init() {
        this.onUserProfileModalEvent();
    },

    onUserProfileModalEvent() {
        const dateFormat = {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit'
        };
        document.getElementById('profileModal').addEventListener('shown.bs.modal', async () => {
            const profileModalBody = document.getElementById("profileModalBody");
            const placeholder = `
            <h5 class="card-title placeholder-glow">
                <span class="placeholder col-4"></span>
                <span class="placeholder col-6"></span>
            </h5>
            <p class="card-text placeholder-glow">
                <span class="placeholder col-4"></span>
                <span class="placeholder col-6"></span>
                <span class="placeholder col-4"></span>
                <span class="placeholder col-6"></span>
                <span class="placeholder col-4"></span>
                <span class="placeholder col-6"></span>
                <span class="placeholder col-4"></span>
                <span class="placeholder col-6"></span>
            </p>
            `;
            profileModalBody.innerHTML = placeholder;
            try {

                const resp = await fetch("./accounts/user.php");
                const result = await resp.json();
                profileModalBody.innerHTML = "";
                const user = result.user;
                let resellerInfo = "";
                let badgeElement = "";
                if (user.accountType == "0") {
                    badgeElement = "<span class='badge text-bg-success'>Reseller</span>";
                    resellerInfo = `
                    <div class="row">
                     <div class="col-4 text-secondary"><img src='./assets/icons/person-card.svg' /> Reseller</div>
                     <div class="col-6">${user['firstName']} ${user['lastName']}</div>
                    </div>
                    <div class="row">
                     <div class="col-4 text-secondary"><img src='./assets/icons/company.svg' /> Company</div>
                     <div class="col-6">${user['companyName']}</div>
                    </div>
                    <div class="row">
                     <div class="col-4 text-secondary"><img src='./assets/icons/telephone.svg' /> Mobile number</div>
                     <div class="col-6">${user['mobileNumber']}</div>
                    </div>
                    <div class="row">
                     <div class="col-4 text-secondary"><img src='./assets/icons/code.svg' /> Reseller code</div>
                     <div class="col-6">${user['resellerCode']}</div>
                    </div>
                    `;
                } else {
                    badgeElement = "<span class='badge text-bg-primary'>Admin</span>";
                }

                const joinedDate = new Date(user['createdAt'].replace(' ', 'T')).toLocaleString('en-US', dateFormat);


                profileModalBody.innerHTML = `
            <div class="row">
                <div class="col-4 text-secondary"><img src='./assets/icons/account.svg' /> Account</div>
                <div class="col-6">${badgeElement}</div>
            </div>
            <div class="row">
                <div class="col-4 text-secondary"><img src='./assets/icons/mail.svg' /> Email</div>
                <div class="col-6">${user.email}</div>
            </div>
            <div class="row">
                <div class="col-4 text-secondary"><img src='./assets/icons/calendar-check.svg' /> Joined</div>
                <div class="col-6">${joinedDate}</div>
            </div>
            ${user.accountType == "0" ? resellerInfo : ""}
            `;


            } catch (err) {
                console.log("ERROR: ", err.message);
            }
        });

    }
}



const SearchInputDropdown = {
    _searchList: [],

    init() {
        this.onInputSearchEvent();
    },

    async searchResellers(query, dataSearch, dropdown) {
        // console.log(GlobalParams.resellerId);
        try {
            const params = new URLSearchParams({ action: "searchDropdown", query, dataSearch, resellerId: GlobalParams.resellerId }).toString();
            const resp = await fetch(`./licenses/license.php?${params}`);
            const result = await resp.json();
            // console.log(result);
            if (!result.success || !result.result) return;

            this._searchList = result.result;
            dropdown.innerHTML = "";

            for (const [index, data] of result.result.entries()) {
                const li = document.createElement("li");
                li.setAttribute("data-idx", index);
                li.textContent = `${data.first_name} ${data.last_name}`;
                dropdown.appendChild(li);
            }
        } catch (error) {
            console.log(error);
        }
    },

    onInputSearchEvent() {
        const searchData = (query, dataSearch, dropdown) => {
            // console.log("search: ", query);
            this.searchResellers(query, dataSearch, dropdown);
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



        document.querySelectorAll(".select-button").forEach(input => {
            const dropdownUL = input.nextElementSibling;

            input.addEventListener('input', (e) => {
                const isDropdownHidden = dropdownUL.classList.contains("hidden");
                if (isDropdownHidden) toggleDropdown(true, dropdownUL); // Open dropdown if it's closed

                const searchTerm = e.target.value;
                const dataSearch = input.getAttribute("data-search");
                debounceHandler(searchTerm, dataSearch, dropdownUL)
            })

            // displaying and selecting option event
            dropdownUL.addEventListener("click", (e) => {
                if (e.target && e.target.matches("li")) {
                    const index = parseInt(e.target.getAttribute('data-idx'));
                    if (isNaN(index)) return;
                    const dataSearch = input.getAttribute("data-search");
                    if (dataSearch == "resellers") {
                        const data = this._searchList[index];
                        document.getElementById("resellerFirstName").value = data.first_name;
                        document.getElementById("resellerLastName").value = data.last_name;
                        document.getElementById("resellerCode").value = data.reseller_code;
                        document.getElementById("technician").value = data.technician;
                        // this._selectedResellerId = data.id;
                        GlobalParams.resellerId = data.id;
                    } else if (dataSearch == "customers") {
                        const data = this._searchList[index];
                        // console.log("customers", data);
                        document.getElementById("customerFirstName").value = data.first_name;
                        document.getElementById("customerLastName").value = data.last_name;
                        document.getElementById("companyName").value = data.company_name;
                        document.getElementById("customerAddress").value = data.address;
                        document.getElementById("customerEmail").value = data.email;
                        document.getElementById("customerContactNumber").value = data.contact_number;
                    }
                }
            });

            input.addEventListener("click", () => toggleDropdown());
            document.addEventListener("click", (e) => {
                if (dropdownUL.classList.contains("hidden")) return;
                if (input.contains(e.target)) return;
                toggleDropdown(false); // when clicked outside
            });

            const toggleDropdown = (expand = null) => {
                const isOpen =
                    expand !== null ? expand : dropdownUL.classList.contains("hidden");
                dropdownUL.classList.toggle("hidden", !isOpen);
                input.setAttribute("aria-expanded", isOpen);
            };
        });
    }
}

SearchInputDropdown.init();
UserProfile.init();
SearchLicense.init();
LicenseGenerator.init();