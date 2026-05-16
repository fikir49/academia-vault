<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvertedIndex extends Model
{
    // Explicitly tell Laravel the table name
    protected $table = 'inverted_indices';
    
    // Allow these fields to be filled with technical data
    protected $fillable = ['term', 'doc_id', 'tf_score', 'technical_context'];
}