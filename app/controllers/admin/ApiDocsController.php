<?php
declare(strict_types=1);
namespace App\Controllers\Admin;
use App\Core\{Auth,Controller,Request,Response,Session};
// Chỉ phục vụ Swagger và đặc tả API cho phiên admin đã xác thực.
final class ApiDocsController extends Controller
{
    public function index(Request $request):void
    {
        Auth::requireRole(['ADMIN']);$this->view('admin/integrations/api-docs',['pageTitle'=>'API & nghiệm thu']);
    }
    public function spec(Request $request):void
    {
        Auth::requireRole(['ADMIN']);
        $spec=json_decode(file_get_contents(__DIR__.'/../../../docs/api/openapi.json'),true,128,JSON_THROW_ON_ERROR);
        $spec['servers']=[['url'=>rtrim((string)config('app.base_url',''),'/') ?: '/']];
        // No credentials are committed to the static contract. A fresh token is scoped to this admin session.
        foreach($spec['paths'] as &$path)foreach($path as &$operation) {
            foreach(($operation['requestBody']['content'] ?? []) as $type=>$content) {
                if(isset($content['schema']['properties']['csrf']))$operation['requestBody']['content'][$type]['schema']['properties']['csrf']['default']=Session::token();
            }
        }
        unset($path,$operation);Response::json($spec);
    }
}
