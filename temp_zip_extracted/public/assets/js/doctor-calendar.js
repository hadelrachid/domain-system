class DoctorCalendarPicker {
    /**
     * @param {string} inputSelector O seletor do campo de data (ex: "#date")
     * @param {string} doctorSelectSelector O seletor do select de medicos (ex: "#doctor_id")
     * @param {Object} doctorDays Objeto contendo os dias de cada medico (ex: { "1": [1,3,5] })
     * @param {Function} onChangeCallback Callback chamado quando a data e alterada
     */
    constructor(inputSelector, doctorSelectSelector, doctorDays, onChangeCallback = null) {
        this.inputSelector = inputSelector;
        this.doctorSelectSelector = doctorSelectSelector;
        this.doctorDays = doctorDays;
        this.onChangeCallback = onChangeCallback;
        this.fpInstance = null;
        this.inputElement = document.querySelector(this.inputSelector);
        this.doctorSelectElement = document.querySelector(this.doctorSelectSelector);
        
        if (!this.inputElement || !this.doctorSelectElement) {
            console.warn("DoctorCalendarPicker: Elementos nao encontrados.", inputSelector, doctorSelectSelector);
            return;
        }

        this.init();
    }

    init() {
        this.doctorSelectElement.addEventListener("change", (e) => {
            this.inputElement.value = "";
            this.updateCalendar(e.target.value);
            if (typeof this.onChangeCallback === "function") {
                this.onChangeCallback([], "", this.fpInstance, true);
            }
        });

        const initialDoc = this.doctorSelectElement.value;
        this.updateCalendar(initialDoc || null);
    }

    updateCalendar(docId) {
        const allowedDays = this.doctorDays[docId] || [];
        
        if (this.fpInstance) {
            this.fpInstance.destroy();
        }
        
        this.fpInstance = flatpickr(this.inputSelector, {
            locale: "pt",
            minDate: "today",
            dateFormat: "Y-m-d",
            disable: [
                (date) => {
                    if (!allowedDays || allowedDays.length === 0) return true;
                    return !allowedDays.includes(date.getDay());
                }
            ],
            onChange: (selectedDates, dateStr, instance) => {
                if (typeof this.onChangeCallback === "function") {
                    this.onChangeCallback(selectedDates, dateStr, instance, false);
                }
            }
        });
    }
}

