// Khởi tạo Swagger từ đặc tả nội bộ, không gửi schema sang dịch vụ kiểm tra bên ngoài.
(() => {
  const root=document.getElementById('swagger-ui');
  if(!root || !window.SwaggerUIBundle)return;
  SwaggerUIBundle({url:root.dataset.specUrl,dom_id:'#swagger-ui',deepLinking:true,
    presets:[SwaggerUIBundle.presets.apis],layout:'BaseLayout',validatorUrl:null,
    persistAuthorization:false,withCredentials:true,defaultModelsExpandDepth:-1,
    requestInterceptor:request=>{
      request.credentials='same-origin';request.headers=request.headers||{};
      Object.keys(request.headers).filter(key=>key.toLowerCase()==='accept').forEach(key=>delete request.headers[key]);
      request.headers.Accept='application/json';
      return request;
    },
  });
})();
