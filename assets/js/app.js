document.querySelectorAll('[data-confirm]').forEach(el=>el.addEventListener('click',e=>{if(!confirm(el.dataset.confirm||'Are you sure?'))e.preventDefault()}));
document.querySelectorAll('input[type=file]').forEach(i=>i.addEventListener('change',()=>{if(i.files[0]&&i.files[0].size>5*1024*1024){alert('Maximum file size is 5 MB');i.value=''}}));
