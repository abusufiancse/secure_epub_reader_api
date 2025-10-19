<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class KeyWrap extends Model {
    protected $fillable = ['ebook_id','wrapped_cek_b64'];
}
