<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionnairePrompt extends Model
{
    public $table = 'questionnaire_prompts';

    protected $fillable = [
        'questionnaire_id',
        'prompt',
    ];

    public function questionnaire()
    {
        return $this->belongsTo(Questionnaire::class);
    }   
}
