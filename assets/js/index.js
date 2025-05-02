const LicenseGenerator = {
    init() {
        this.onSubmitFormEvent();
        this.numberValidationEvent();
    },

    onSubmitFormEvent() {
        const form = document.getElementById("generateLicenseForm")
        form.addEventListener("submit", (e) => {
            e.preventDefault();
            if (!form.checkValidity()) return;

            const formData = new FormData(e.target);
            formData.append("licenseInitialIssuedDate", Date.now())

            const data = Object.fromEntries(formData.entries());

            const toastBootstrap = bootstrap.Toast.getOrCreateInstance(document.getElementById("liveToast"))
            toastBootstrap.show();
            console.log("JSON: ", JSON.stringify(data))
            form.reset();
        })
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