document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('[data-crud-search]').forEach(function(container){
    var tableId = container.dataset.crudSearch;
    var input = container.querySelector('input');
    var table = document.getElementById(tableId);
    var counter = container.querySelector('[data-count]');
    function getRows(){ return table.querySelectorAll('tbody tr'); }
    var total = getRows().length;
    if(counter) counter.textContent = total + ' registros';
    var to;
    input.addEventListener('input', function(){
      clearTimeout(to);
      to = setTimeout(function(){
        var q = input.value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'');
        var visible = 0;
        getRows().forEach(function(tr){
          var txt = tr.innerText.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'');
          tr.style.display = show ? '' : 'none';
          if(show) visible++;
        });
        if(counter) counter.textContent = (q ? visible + '/' + total : total) + ' registros';
      }, 100);
    });
    var clearBtn = container.querySelector('[data-clear]');
    if(clearBtn){ clearBtn.addEventListener('click', function(){ input.value=''; input.dispatchEvent(new Event('input')); input.focus(); }); }
  });
});
