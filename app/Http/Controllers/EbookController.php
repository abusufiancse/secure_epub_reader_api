<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Ebook;
use App\Models\Purchase;
use App\Models\KeyWrap;
use App\Models\DownloadToken;
use App\Services\CryptoService;
use Illuminate\Support\Facades\Storage;

class EbookController extends Controller {
    public function index() {
        return Ebook::select('id','title','author','is_free','price_cents')->get();
    }
    public function show($id) {
        return Ebook::findOrFail($id);
    }
    public function issueToken(Request $r, $id) {
        $ebook = Ebook::findOrFail($id);
        $user  = $r->user();
        if (!$ebook->is_free) {
            $has = Purchase::where('user_id',$user->id)->where('ebook_id',$ebook->id)->where('status','paid')->exists();
            if (!$has) return response()->json(['message'=>'Not purchased'], 403);
        }
        $token = bin2hex(random_bytes(16));
        $ttl = (int)env('DOWNLOAD_TTL',300);
        DownloadToken::create([
            'user_id'=>$user->id,'ebook_id'=>$ebook->id,'token'=>$token,'expires_at'=>now()->addSeconds($ttl)
        ]);
        return response()->json([
            'downloadUrl'=> url('/api/ebooks/'.$ebook->id.'/download?token='.$token),
            'keyUrl'=>      url('/api/ebooks/'.$ebook->id.'/key?token='.$token),
            'expiresIn'=> $ttl
        ]);
    }
    public function download(Request $r, $id) {
        $ebook = Ebook::findOrFail($id);
        $token = $r->query('token');
        $dt = DownloadToken::where('ebook_id',$id)->where('token',$token)->where('expires_at','>',now())->first();
        if (!$dt) return response()->json(['message'=>'Token invalid/expired'], 403);
        $json = Storage::get($ebook->enc_path);
        return response($json,200)->header('Content-Type','application/json');
    }
    public function issueKey(Request $r, $id) {
        $ebook = Ebook::findOrFail($id);
        $token = $r->query('token');
        $dt = DownloadToken::where('ebook_id',$id)->where('token',$token)->where('expires_at','>',now())->first();
        if (!$dt) return response()->json(['message'=>'Token invalid/expired'], 403);
        $wrap = KeyWrap::where('ebook_id',$id)->firstOrFail();
        $kek  = base64_decode(env('BOOKS_MASTER_KEK_B64'));
        $cek  = CryptoService::unwrapCEK($wrap->wrapped_cek_b64, $kek);
        return response()->json(['v'=>1,'cek_b64'=> base64_encode($cek),'ttl'=> (int)env('KEY_TTL',120)]);
    }
}
