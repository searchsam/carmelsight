document.addEventListener('DOMContentLoaded', function () {
  const authorSelect = document.getElementById('ms-author');
  const bookSelect = document.getElementById('ms-book');
  const form = document.getElementById('ms-search-form');
  const results = document.getElementById('ms-results');

  authorSelect.addEventListener('change', function () {
    const authorID = this.value;
    bookSelect.innerHTML = '<option value="">Cargando...</option>';

    fetch(`${ms_ajax.url}?action=ms_get_books&author_id=${authorID}`)
      .then(res => res.json())
      .then(data => {
        bookSelect.innerHTML = '<option value="">Seleccione un libro</option>';
        data.forEach(book => {
          const opt = document.createElement('option');
          opt.value = book.id;
          opt.textContent = book.name;
          bookSelect.appendChild(opt);
        });
      });
  });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'ms_search_keyword');

    fetch(ms_ajax.url, {
      method: 'POST',
      body: formData
    }).then(r => r.text())
      .then(html => {
        results.innerHTML = html;
        paginateTable('ms-table-results', 'ms-pagination', 10); // 10 rows per page
      });
  });
});

function paginateTable(tableId, paginationId, rowsPerPage = 10) {
  const table = document.getElementById(tableId);
  const tbody = table.querySelector('tbody');
  const rows = Array.from(tbody.querySelectorAll('tr'));
  const totalPages = Math.ceil(rows.length / rowsPerPage);
  const pagination = document.getElementById(paginationId);

  let currentPage = 1;

  function showPage(page) {
    currentPage = page;
    const start = (page - 1) * rowsPerPage;
    const finish = start + rowsPerPage;

    rows.forEach((row, i) => {
      row.style.display = (i >= start && i < finish) ? '' : 'none';
    });

    renderPagination();
  }

  function renderPagination() {
    pagination.innerHTML = '';
    for (let i = 1; i <= totalPages; i++) {
      const btn = document.createElement('button');
      btn.textContent = i;
      btn.className = (i === currentPage) ? 'active' : '';
      btn.onclick = () => showPage(i);
      pagination.appendChild(btn);
    }
  }

  if (rows.length > 0) {
    showPage(1);
  }
}

// Run after displaying results
form.addEventListener('submit', function (e) {
  e.preventDefault();
  const formData = new FormData(this);
  formData.append('action', 'ms_search_keyword');

  fetch(ms_ajax.url, {
    method: 'POST',
    body: formData
  }).then(r => r.text())
    .then(html => {
      results.innerHTML = html;
      paginateTable('ms-table-results', 'ms-pagination', 10); // 10 rows per page
    });
});
