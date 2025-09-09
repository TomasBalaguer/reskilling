<div x-data="scaleQuestion()" x-init="initScale()">
    <!-- Scale Type: Visual Scale -->
    <div x-show="scaleType === 'visual'">
        <div class="text-center mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-muted small" x-text="minLabel"></span>
                <span class="text-muted small" x-text="maxLabel"></span>
            </div>
            
            <div class="scale-container d-flex justify-content-between align-items-center">
                <template x-for="value in scaleValues" :key="value">
                    <div class="scale-option text-center">
                        <input 
                            type="radio" 
                            :name="radioName"
                            :id="'scale_' + value"
                            :value="value"
                            x-model="selectedValue"
                            @change="updateResponse()"
                            class="scale-radio">
                        <label 
                            :for="'scale_' + value" 
                            class="scale-label"
                            :class="{ 'selected': selectedValue == value }">
                            <div class="scale-circle"></div>
                            <div class="scale-number" x-text="value"></div>
                        </label>
                    </div>
                </template>
            </div>
        </div>
    </div>
    
    <!-- Scale Type: Slider -->
    <div x-show="scaleType === 'slider'">
        <div class="mb-4">
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted small" x-text="minLabel"></span>
                <span class="badge bg-primary" x-show="selectedValue !== null">
                    <span x-text="selectedValue"></span>
                </span>
                <span class="text-muted small" x-text="maxLabel"></span>
            </div>
            
            <input 
                type="range" 
                class="form-range" 
                :min="minValue" 
                :max="maxValue" 
                :step="stepValue"
                x-model="selectedValue"
                @input="updateResponse()"
                style="width: 100%;">
            
            <div class="d-flex justify-content-between text-muted small mt-1">
                <span x-text="minValue"></span>
                <span x-text="maxValue"></span>
            </div>
        </div>
    </div>
    
    <!-- Scale Type: Button Grid -->
    <div x-show="scaleType === 'buttons'">
        <div class="row g-2">
            <template x-for="value in scaleValues" :key="value">
                <div class="col">
                    <button 
                        type="button"
                        class="btn w-100"
                        :class="selectedValue == value ? 'btn-primary' : 'btn-outline-primary'"
                        @click="selectValue(value)"
                        x-text="value">
                    </button>
                </div>
            </template>
        </div>
        
        <div class="d-flex justify-content-between text-muted small mt-2">
            <span x-text="minLabel"></span>
            <span x-text="maxLabel"></span>
        </div>
    </div>
    
    <!-- Current Selection Display -->
    <div class="text-center mt-3" x-show="selectedValue !== null">
        <div class="alert alert-success py-2">
            <i class="fas fa-check-circle"></i>
            Seleccionaste: <strong x-text="selectedValue"></strong>
            <span x-show="getValueLabel(selectedValue)" x-text="' - ' + getValueLabel(selectedValue)"></span>
        </div>
    </div>
</div>

<style>
.scale-container {
    padding: 1rem 0;
}

.scale-option {
    position: relative;
    cursor: pointer;
    transition: all 0.2s ease;
}

.scale-radio {
    position: absolute;
    opacity: 0;
    cursor: pointer;
}

.scale-label {
    display: block;
    cursor: pointer;
    transition: all 0.2s ease;
}

.scale-circle {
    width: 40px;
    height: 40px;
    border: 3px solid var(--border-color);
    border-radius: 50%;
    background: white;
    margin: 0 auto 0.5rem;
    transition: all 0.2s ease;
    position: relative;
}

.scale-circle::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0);
    width: 20px;
    height: 20px;
    background: var(--primary-color);
    border-radius: 50%;
    transition: transform 0.2s ease;
}

.scale-label.selected .scale-circle {
    border-color: var(--primary-color);
    transform: scale(1.1);
}

.scale-label.selected .scale-circle::after {
    transform: translate(-50%, -50%) scale(1);
}

.scale-number {
    font-weight: 600;
    color: var(--medium-gray);
    transition: color 0.2s ease;
}

.scale-label.selected .scale-number {
    color: var(--primary-color);
}

.scale-label:hover .scale-circle {
    border-color: var(--primary-color);
    transform: scale(1.05);
}

.form-range {
    height: 8px;
    background: linear-gradient(90deg, #e5e7eb 0%, var(--primary-color) 100%);
    border-radius: 4px;
}

.form-range::-webkit-slider-thumb {
    width: 24px;
    height: 24px;
    background: var(--primary-color);
    border: 3px solid white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.form-range::-moz-range-thumb {
    width: 24px;
    height: 24px;
    background: var(--primary-color);
    border: 3px solid white;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
</style>

<script>
function scaleQuestion() {
    return {
        selectedValue: null,
        minValue: 1,
        maxValue: 10,
        stepValue: 1,
        minLabel: 'Muy bajo',
        maxLabel: 'Muy alto',
        scaleType: 'visual', // visual, slider, buttons
        valueLabels: {},
        radioName: 'scale_' + Math.random().toString(36).substr(2, 9),
        
        get scaleValues() {
            const values = [];
            for (let i = this.minValue; i <= this.maxValue; i += this.stepValue) {
                values.push(i);
            }
            return values;
        },
        
        initScale() {
            console.log('=== SCALE COMPONENT INIT ===');
            
            // Wait for questionnaire to be ready or use it if already available
            if (window.questionnaireApp) {
                console.log('Questionnaire already available for scale');
                this.setupScale();
            } else {
                console.log('Waiting for questionnaire to be ready for scale...');
                document.addEventListener('questionnaire-ready', () => {
                    console.log('Questionnaire ready event received by scale');
                    this.setupScale();
                }, { once: true });
                
                // Fallback timeout
                setTimeout(() => {
                    console.log('Scale setup timeout, trying anyway...');
                    this.setupScale();
                }, 2000);
            }
        },
        
        setupScale() {
            try {
                const questionnaireApp = window.questionnaireApp;
                if (!questionnaireApp) {
                    console.warn('No questionnaire app available for scale setup');
                    return;
                }
                
                const currentQuestion = questionnaireApp.currentQuestion;
                const currentSection = questionnaireApp.currentSection;
                
                // Configure scale from section or question
                const config = currentSection || currentQuestion || {};
                
                if (config.min_value !== undefined) this.minValue = config.min_value;
                if (config.max_value !== undefined) this.maxValue = config.max_value;
                if (config.step_value !== undefined) this.stepValue = config.step_value;
                if (config.min_label) this.minLabel = config.min_label;
                if (config.max_label) this.maxLabel = config.max_label;
                if (config.scale_type) this.scaleType = config.scale_type;
                if (config.value_labels) this.valueLabels = config.value_labels;
                
                // Common scale configurations
                if (config.response_type === 'rating' || config.response_type === 'scale') {
                    // Default rating scale
                    this.minValue = 1;
                    this.maxValue = 5;
                    this.minLabel = 'Muy bajo';
                    this.maxLabel = 'Muy alto';
                    this.scaleType = 'visual';
                }
                
                // Load existing response if any
                const existingResponse = questionnaireApp.getCurrentResponse();
                if (existingResponse !== null && existingResponse !== undefined) {
                    this.selectedValue = parseInt(existingResponse);
                }
                
                console.log('Scale setup complete:', {
                    minValue: this.minValue,
                    maxValue: this.maxValue,
                    scaleType: this.scaleType,
                    selectedValue: this.selectedValue
                });
                
            } catch (error) {
                console.error('Error setting up scale:', error);
            }
        },
        
        selectValue(value) {
            this.selectedValue = value;
            this.updateResponse();
        },
        
        updateResponse() {
            try {
                const questionnaireApp = window.questionnaireApp;
                
                if (questionnaireApp && typeof questionnaireApp.setResponse === 'function') {
                    if (this.selectedValue !== null && this.selectedValue !== undefined) {
                        questionnaireApp.setResponse(parseInt(this.selectedValue));
                        console.log('Scale response updated:', this.selectedValue);
                    } else {
                        questionnaireApp.setResponse(null);
                    }
                } else {
                    console.error('Cannot update scale response - questionnaire app not available');
                }
            } catch (error) {
                console.error('Error updating scale response:', error);
            }
        },
        
        getValueLabel(value) {
            return this.valueLabels[value] || '';
        }
    }
}
</script>