<div x-data="multipleChoiceQuestion()" x-init="initMultipleChoice()">
    <div class="row">
        <template x-for="(option, index) in options" :key="option.value || index">
            <div class="col-12 mb-3">
                <div class="form-check">
                    <input 
                        class="form-check-input" 
                        type="checkbox" 
                        :id="'option_' + index"
                        :value="option.value || option.text || option"
                        @change="toggleOption(option.value || option.text || option)">
                    <label 
                        class="form-check-label w-100" 
                        :for="'option_' + index"
                        style="cursor: pointer;">
                        <div class="card border-0 shadow-sm h-100" 
                             :class="{ 'border-primary bg-light': selectedValues.includes(option.value || option.text || option) }">
                            <div class="card-body py-3">
                                <div class="d-flex align-items-center">
                                    <!-- Option Icon (if available) -->
                                    <div class="me-3" x-show="option.icon">
                                        <i :class="option.icon + ' fa-2x text-primary'"></i>
                                    </div>
                                    
                                    <!-- Option Content -->
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1" x-text="option.text || option.label || option"></h6>
                                        <p class="text-muted small mb-0" x-show="option.description" x-text="option.description"></p>
                                    </div>
                                    
                                    <!-- Selected Indicator -->
                                    <div class="ms-2" x-show="selectedValues.includes(option.value || option.text || option)">
                                        <i class="fas fa-check-circle text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </label>
                </div>
            </div>
        </template>
    </div>
    
    <!-- Selection Info -->
    <div class="mt-3">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <span class="badge bg-primary" x-show="selectedValues.length > 0">
                    <span x-text="selectedValues.length"></span> seleccionada(s)
                </span>
                <span class="text-muted small" x-show="selectedValues.length === 0">
                    Selecciona una o más opciones
                </span>
            </div>
            
            <div class="text-muted small" x-show="maxSelections > 0">
                Máximo: <span x-text="maxSelections"></span>
            </div>
        </div>
        
        <!-- Validation Messages -->
        <div class="text-success small mt-2" x-show="isValidSelection">
            <i class="fas fa-check-circle"></i> Selección válida
        </div>
        
        <div class="text-warning small mt-2" x-show="selectedValues.length > 0 && !isValidSelection">
            <i class="fas fa-exclamation-triangle"></i>
            <span x-text="getValidationMessage()"></span>
        </div>
    </div>
</div>

<script>
function multipleChoiceQuestion() {
    return {
        selectedValues: [],
        options: [],
        minSelections: 1,
        maxSelections: 0, // 0 means no limit
        
        get isValidSelection() {
            const count = this.selectedValues.length;
            const minOk = count >= this.minSelections;
            const maxOk = this.maxSelections === 0 || count <= this.maxSelections;
            return minOk && maxOk;
        },
        
        initMultipleChoice() {
            // Get question configuration
            const questionnaireApp = window.questionnaireApp;
            const currentQuestion = questionnaireApp.currentQuestion;
            const currentSection = questionnaireApp.currentSection;
            
            // Load options from section or question
            this.options = currentSection?.options || currentQuestion?.options || [];
            
            // If options are simple strings, convert to objects
            this.options = this.options.map(option => {
                if (typeof option === 'string') {
                    return { text: option, value: option };
                }
                return option;
            });
            
            // Set selection limits
            if (currentSection?.min_items) {
                this.minSelections = currentSection.min_items;
            }
            if (currentSection?.max_items) {
                this.maxSelections = currentSection.max_items;
            }
            if (currentQuestion?.min_selections) {
                this.minSelections = currentQuestion.min_selections;
            }
            if (currentQuestion?.max_selections) {
                this.maxSelections = currentQuestion.max_selections;
            }
            
            // Load existing response if any
            const existingResponse = questionnaireApp.getCurrentResponse();
            if (Array.isArray(existingResponse)) {
                this.selectedValues = [...existingResponse];
                // Update checkboxes
                this.$nextTick(() => {
                    this.updateCheckboxes();
                });
            }
        },
        
        toggleOption(value) {
            const index = this.selectedValues.indexOf(value);
            
            if (index > -1) {
                // Remove if already selected
                this.selectedValues.splice(index, 1);
            } else {
                // Add if not selected (respecting max limit)
                if (this.maxSelections === 0 || this.selectedValues.length < this.maxSelections) {
                    this.selectedValues.push(value);
                } else {
                    // Uncheck the checkbox since we can't add more
                    this.$nextTick(() => {
                        const checkbox = this.$el.querySelector(`input[value="${value}"]`);
                        if (checkbox) checkbox.checked = false;
                    });
                    return;
                }
            }
            
            this.updateResponse();
        },
        
        updateResponse() {
            const questionnaireApp = window.questionnaireApp;
            
            if (this.isValidSelection) {
                questionnaireApp.setResponse([...this.selectedValues]);
            } else {
                questionnaireApp.setResponse(null);
            }
        },
        
        updateCheckboxes() {
            this.options.forEach((option, index) => {
                const value = option.value || option.text || option;
                const checkbox = this.$el.querySelector(`#option_${index}`);
                if (checkbox) {
                    checkbox.checked = this.selectedValues.includes(value);
                }
            });
        },
        
        getValidationMessage() {
            const count = this.selectedValues.length;
            
            if (count < this.minSelections) {
                const needed = this.minSelections - count;
                return `Debes seleccionar al menos ${needed} opción(es) más`;
            }
            
            if (this.maxSelections > 0 && count > this.maxSelections) {
                const excess = count - this.maxSelections;
                return `Has seleccionado ${excess} opción(es) de más`;
            }
            
            return '';
        }
    }
}
</script>