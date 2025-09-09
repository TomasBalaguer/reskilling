<div x-data="textQuestion()" x-init="initText()">
    <div class="form-group">
        <textarea 
            class="form-control"
            :class="{ 'border-success': hasValidResponse }"
            rows="6"
            :placeholder="getPlaceholder()"
            x-model="textValue"
            @input="updateResponse()"
            :maxlength="maxLength"
            style="resize: vertical; min-height: 150px;">
        </textarea>
        
        <!-- Character count -->
        <div class="d-flex justify-content-between align-items-center mt-2">
            <small class="text-muted">
                <i class="fas fa-info-circle"></i>
                Escribe tu respuesta de forma clara y detallada
            </small>
            <small class="text-muted" x-show="maxLength > 0">
                <span x-text="textValue.length"></span> / <span x-text="maxLength"></span> caracteres
            </small>
        </div>
        
        <!-- Validation message -->
        <div class="text-success small mt-1" x-show="hasValidResponse">
            <i class="fas fa-check-circle"></i> Respuesta válida
        </div>
        
        <div class="text-warning small mt-1" x-show="textValue.length > 0 && !hasValidResponse">
            <i class="fas fa-exclamation-triangle"></i> 
            <span x-text="getValidationMessage()"></span>
        </div>
    </div>
</div>

<script>
function textQuestion() {
    return {
        textValue: '',
        minLength: 10,
        maxLength: 2000,
        
        get hasValidResponse() {
            return this.textValue.trim().length >= this.minLength;
        },
        
        initText() {
            // Wait for questionnaire to be ready
            if (window.questionnaireApp) {
                this.setupText();
            } else {
                document.addEventListener('questionnaire-ready', () => {
                    this.setupText();
                }, { once: true });
                setTimeout(() => this.setupText(), 2000);
            }
        },
        
        setupText() {
            try {
                const questionnaireApp = window.questionnaireApp;
                if (!questionnaireApp) return;
                
                const currentQuestion = questionnaireApp.currentQuestion;
                const currentSection = questionnaireApp.currentSection;
                
                // Set length limits from question config
                if (currentQuestion?.min_length) {
                    this.minLength = currentQuestion.min_length;
                }
                if (currentQuestion?.max_length) {
                    this.maxLength = currentQuestion.max_length;
                }
                if (currentSection?.min_items) {
                    this.minLength = currentSection.min_items;
                }
                if (currentSection?.max_items) {
                    this.maxLength = currentSection.max_items;
                }
                
                // Load existing response if any
                const existingResponse = questionnaireApp.getCurrentResponse();
                if (existingResponse && typeof existingResponse === 'string') {
                    this.textValue = existingResponse;
                }
            } catch (error) {
                console.error('Error setting up text component:', error);
            }
        },
        
        updateResponse() {
            try {
                const questionnaireApp = window.questionnaireApp;
                const value = this.textValue.trim();
                
                if (questionnaireApp && typeof questionnaireApp.setResponse === 'function') {
                    if (this.hasValidResponse) {
                        questionnaireApp.setResponse(value);
                    } else {
                        questionnaireApp.setResponse(null);
                    }
                }
            } catch (error) {
                console.error('Error updating text response:', error);
            }
        },
        
        getPlaceholder() {
            const questionnaireApp = window.questionnaireApp;
            const currentQuestion = questionnaireApp.currentQuestion;
            
            if (currentQuestion?.placeholder) {
                return currentQuestion.placeholder;
            }
            
            return `Escribe tu respuesta aquí (mínimo ${this.minLength} caracteres)...`;
        },
        
        getValidationMessage() {
            const remaining = this.minLength - this.textValue.trim().length;
            return `Necesitas escribir al menos ${remaining} caracteres más`;
        }
    }
}
</script>