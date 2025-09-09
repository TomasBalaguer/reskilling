<div x-data="singleChoiceQuestion()" x-init="initSingleChoice()">
    <div class="row">
        <template x-for="(option, index) in options" :key="option.value || index">
            <div class="col-12 mb-3">
                <div class="form-check">
                    <input 
                        class="form-check-input" 
                        type="radio" 
                        :name="radioName"
                        :id="'option_' + index"
                        :value="option.value || option.text || option"
                        x-model="selectedValue"
                        @change="updateResponse()">
                    <label 
                        class="form-check-label w-100" 
                        :for="'option_' + index"
                        style="cursor: pointer;">
                        <div class="card border-0 shadow-sm h-100" 
                             :class="{ 'border-primary bg-light': selectedValue === (option.value || option.text || option) }">
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
                                    <div class="ms-2" x-show="selectedValue === (option.value || option.text || option)">
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
    
    <!-- Validation message -->
    <div class="text-success small mt-2" x-show="selectedValue">
        <i class="fas fa-check-circle"></i> Opción seleccionada
    </div>
</div>

<script>
function singleChoiceQuestion() {
    return {
        selectedValue: null,
        options: [],
        radioName: 'radio_' + Math.random().toString(36).substr(2, 9),
        
        initSingleChoice() {
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
            
            // Load existing response if any
            const existingResponse = questionnaireApp.getCurrentResponse();
            if (existingResponse) {
                this.selectedValue = existingResponse;
            }
        },
        
        updateResponse() {
            const questionnaireApp = window.questionnaireApp;
            questionnaireApp.setResponse(this.selectedValue);
        }
    }
}
</script>