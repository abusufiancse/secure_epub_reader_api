<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Ebook extends Model {
    protected $fillable = ['title','author','is_free','price_cents','enc_path'];
}
