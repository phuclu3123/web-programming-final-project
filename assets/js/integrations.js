// Gửi form nguồn coupon qua AJAX; giữ input khi lỗi và chỉ chuyển tới cùng origin.
document.addEventListener('submit', async event => {
  const form=event.target.closest('[data-integration-form]');
  if(!form || event.defaultPrevented) return;
  event.preventDefault();
  if(form.dataset.busy==='1') return;
  const button=form.querySelector('button[type="submit"],button:not([type])');
  const message=form.querySelector('[data-integration-message]');
  form.querySelectorAll('[data-integration-error]').forEach(error=>{
    const field=form.elements.namedItem(error.dataset.integrationError);
    if(field){field.removeAttribute('aria-invalid');field.classList.remove('is-invalid');
      const ids=(field.getAttribute('aria-describedby')||'').split(' ').filter(id=>id!==error.id&&id);
      if(ids.length)field.setAttribute('aria-describedby',ids.join(' '));else field.removeAttribute('aria-describedby');}
    error.remove();
  });
  message.replaceChildren();message.hidden=true;
  form.dataset.busy='1';if(button)button.disabled=true;
  try {
    const response=await fetch(form.action,{method:'POST',credentials:'same-origin',headers:{Accept:'application/json'},body:new FormData(form)});
    let data;try{data=await response.json();}catch{throw new Error('Máy chủ chưa trả kết quả hợp lệ. Vui lòng tải lại trang hoặc thử lại.');}
    if(!response.ok || data.success!==true){
      const error=new Error(data.message||'Kiểm tra lại dữ liệu.');error.fields=data.errors;throw error;
    }
    if(data.redirect){const target=new URL(data.redirect,location.href);if(target.origin!==location.origin)throw new Error('Đường dẫn không hợp lệ.');location.assign(target.href);}
    else {message.textContent=data.message;message.hidden=false;message.className='alert alert-success';}
  } catch(error){
    message.textContent=error instanceof TypeError?'Không kết nối được máy chủ. Thông tin bạn nhập vẫn được giữ lại.':(error.message||'Chưa thể xử lý. Vui lòng thử lại.');
    if(error.fields&&typeof error.fields==='object'){
      const list=document.createElement('ul');list.className='mb-0 mt-2';
      Object.entries(error.fields).forEach(([name,text])=>{
        const field=form.elements.namedItem(name),item=document.createElement('li');
        if(field&&field.id&&field.type!=='hidden'){
          const id=field.id+'-error',inline=document.createElement('div'),link=document.createElement('a');
          inline.id=id;inline.className='invalid-feedback';inline.dataset.integrationError=name;inline.textContent=String(text);
          field.classList.add('is-invalid');field.setAttribute('aria-invalid','true');
          field.setAttribute('aria-describedby',((field.getAttribute('aria-describedby')||'')+' '+id).trim());field.after(inline);
          link.href='#'+field.id;link.textContent=String(text);link.addEventListener('click',()=>field.focus());item.append(link);
        }else item.textContent=String(text);
        list.append(item);
      });message.append(list);
    }
    message.hidden=false;message.className='alert alert-danger';message.focus();
  }
  finally{delete form.dataset.busy;if(button)button.disabled=false;}
});
