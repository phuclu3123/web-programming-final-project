// Chọn trong trang đang xem; gửi đúng ID và phiên bản, hiển thị lỗi riêng từng mục.
document.querySelectorAll('[data-bulk-scope]').forEach(scope => {
  const form=scope.querySelector('[data-bulk-form]');
  if(!form)return;
  const all=form.querySelector('[data-bulk-all]'),button=form.querySelector('[data-bulk-submit]');
  const counter=form.querySelector('[data-bulk-count]'),message=form.querySelector('[data-bulk-message]');
  const items=()=>[...scope.querySelectorAll('[data-bulk-item]:not(:disabled)')];
  const selected=()=>items().filter(item=>item.checked);
  const update=()=>{
    const available=items(),chosen=selected();
    counter.textContent=`Đã chọn ${chosen.length} mục`;
    all.checked=available.length>0&&chosen.length===available.length;
    all.indeterminate=chosen.length>0&&chosen.length<available.length;
    all.disabled=available.length===0||form.dataset.busy==='1';
    button.disabled=chosen.length===0||form.dataset.busy==='1';
    const note=form.elements.namedItem('note');
    if(note)note.required=form.elements.namedItem('action').value==='REJECT';
  };
  all.addEventListener('change',()=>{items().forEach(item=>{item.checked=all.checked;});update();});
  scope.addEventListener('change',update);
  form.addEventListener('submit',async event=>{
    event.preventDefault();if(form.dataset.busy==='1')return;
    const chosen=selected();if(!chosen.length)return;
    const action=form.elements.namedItem('action');
    if(!window.confirm(`${action.selectedOptions[0].textContent} cho ${chosen.length} mục đã chọn?`))return;
    form.elements.namedItem('selection').value=JSON.stringify(chosen.map(item=>({id:Number(item.dataset.id),fingerprint:item.dataset.fingerprint})));
    form.dataset.busy='1';form.setAttribute('aria-busy','true');update();
    message.hidden=false;message.className='alert alert-info mt-3 mb-0';message.textContent='Đang xử lý các mục đã chọn…';
    // Chụp FormData trước khi khóa các control để không mất dữ liệu khỏi request.
    const body=new FormData(form);const controls=[...scope.querySelectorAll('input,select,button')].filter(control=>!control.disabled);
    controls.forEach(control=>{control.disabled=true;});
    try{
      const target=new URL(form.getAttribute('action'),location.href);if(target.origin!==location.origin)throw new Error('Đường dẫn xử lý không hợp lệ.');
      const response=await fetch(target,{method:'POST',credentials:'same-origin',headers:{Accept:'application/json'},body});
      let data;try{data=await response.json();}catch{throw new Error('Máy chủ chưa trả kết quả hợp lệ. Tải lại để kiểm tra trước khi thử lại.');}
      if(!response.ok||data.success!==true)throw new Error(data.message||'Chưa thể xử lý.');
      const succeeded=new Set();
      (data.results||[]).forEach(result=>{
        const row=[...scope.querySelectorAll('[data-bulk-row]')].find(row=>row.dataset.bulkRow===String(result.id));
        if(!row)return;
        const output=row.querySelector('[data-bulk-result]');output.textContent=result.message;output.className=`small mt-2 ${result.success?'text-success':'text-danger'}`;
        if(result.success)succeeded.add(String(result.id));
      });
      chosen.forEach(item=>{if(succeeded.has(item.dataset.id)){item.checked=false;item.dataset.processed='1';}});
      message.className=`alert ${data.failed?'alert-warning':'alert-success'} mt-3 mb-0`;message.textContent=data.message;
      const refresh=document.createElement('a');refresh.href=location.href;refresh.className='d-block mt-2';refresh.textContent='Tải lại danh sách';message.append(refresh);
    }catch(error){message.className='alert alert-danger mt-3 mb-0';message.textContent=error instanceof TypeError?'Mất kết nối. Tải lại để xác nhận kết quả trước khi thử lại.':error.message;}
    finally{
      controls.forEach(control=>{control.disabled=false;});
      scope.querySelectorAll('[data-bulk-item][data-processed]').forEach(item=>{item.disabled=true;});
      delete form.dataset.busy;form.removeAttribute('aria-busy');update();message.focus();
    }
  });
  update();
});
