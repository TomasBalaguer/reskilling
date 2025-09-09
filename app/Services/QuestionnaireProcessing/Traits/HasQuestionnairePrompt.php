<?php

namespace App\Services\QuestionnaireProcessing\Traits;

use App\Models\QuestionnairePrompt;

trait HasQuestionnairePrompt
{
    /**
     * Get AI-specific instructions for interpreting this questionnaire type
     *
     * @param string $defaultInstructions The default instructions to use if no prompt is found
     * @return string Formatted instructions
     */
    protected function getInstructionsWithPrompt(string $defaultInstructions): string
    {
        // Get the prompt from QuestionnairePrompt table
        $questionnairePrompt = QuestionnairePrompt::where('questionnaire_id', function($query) {
            $query->select('id')
                ->from('questionnaires')
                ->where('code', $this->type)
                ->first();
        })
        ->where('is_active', true)
        ->first();

        $instructions = "";

        // Add the prompt from the database if it exists
        if ($questionnairePrompt) {
            $instructions .= $questionnairePrompt->prompt . "\n\n";
        }else{
            $instructions .= $defaultInstructions;
        }

        return $instructions;
    }
} 