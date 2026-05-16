<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LexicalDictionary extends Model
{
    protected $table = 'lexical_dictionary';
    
    protected $fillable = ['word', 'definition', 'language_code'];
}