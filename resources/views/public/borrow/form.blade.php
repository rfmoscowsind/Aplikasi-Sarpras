@extends('layouts.public')
@section('title', 'Peminjaman '.$unit->name)
@section('heading', 'Peminjaman '.$unit->name)
@section('subheading', 'Cari data siswa dan pilih barang yang akan dipinjam.')
@section('content')
<div class="panel">
    <div class="panel-head"><h2>Ajukan Peminjaman</h2></div>
    <div class="panel-body">
        <form method="post" action="{{ route('public.borrow.store', $unit->borrow_public_token) }}" class="stack" id="borrow-form">
            @csrf
            <input type="hidden" name="student_id" id="student-id">

            <div class="field search-box">
                <label>Nama / NIS Siswa</label>
                <input class="input" id="student-search" autocomplete="off" placeholder="Ketik minimal 3 karakter..." required>
                <div class="search-results" id="student-results" hidden></div>
            </div>
            <div id="selected-student" class="card" hidden></div>

            <div class="field">
                <label>Nomor HP aktif</label>
                <input class="input" name="phone" type="tel" inputmode="tel" placeholder="08xxxxxxxxxx" required>
            </div>

            <div class="field search-box">
                <label>Cari Barang</label>
                <input class="input" id="item-search" autocomplete="off" placeholder="Contoh: proyektor">
                <div class="search-results" id="item-results" hidden></div>
            </div>

            <div>
                <div class="small muted" style="margin-bottom:6px">Barang dipilih</div>
                <div id="selected-items" class="card"><div class="empty" id="item-empty">Belum ada barang dipilih.</div></div>
            </div>

            <div class="field">
                <label>Keperluan</label>
                <textarea class="textarea" name="purpose" required placeholder="Barang digunakan untuk..."></textarea>
            </div>

            <div class="field">
                <label>Perkiraan Pengembalian</label>
                <input class="input" type="datetime-local" name="expected_return_at" required>
            </div>

            <button class="btn primary" type="submit">Kirim Permintaan</button>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-head"><h2>Mau Mengembalikan Barang?</h2></div>
    <div class="panel-body stack">
        <p class="muted" style="margin:0">Cari transaksi aktif dengan nama, NIS, atau kode transaksi.</p>
        <div class="field search-box">
            <label>Cari transaksi</label>
            <input class="input" id="loan-search" autocomplete="off" placeholder="Minimal 3 karakter">
            <div class="search-results" id="loan-results" hidden></div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
    const studentUrl = @json(route('public.borrow.students', $unit->borrow_public_token));
    const itemUrl = @json(route('public.borrow.items', $unit->borrow_public_token));
    const loanUrl = @json(route('public.borrow.active-loans', $unit->borrow_public_token));
    const transactionBase = @json(url('/pinjam/'.$unit->borrow_public_token.'/transaksi'));

    const studentInput = document.getElementById('student-search');
    const studentResults = document.getElementById('student-results');
    const studentId = document.getElementById('student-id');
    const selectedStudent = document.getElementById('selected-student');

    const itemInput = document.getElementById('item-search');
    const itemResults = document.getElementById('item-results');
    const selectedItems = document.getElementById('selected-items');
    const itemEmpty = document.getElementById('item-empty');
    const selected = new Map();

    const loanInput = document.getElementById('loan-search');
    const loanResults = document.getElementById('loan-results');

    let studentTimer;
    studentInput.addEventListener('input', function () {
        clearTimeout(studentTimer);
        const q = studentInput.value.trim();
        studentId.value = '';
        selectedStudent.hidden = true;
        if (q.length < 3) {
            studentResults.hidden = true;
            return;
        }
        studentTimer = setTimeout(async function () {
            const response = await fetch(studentUrl + '?q=' + encodeURIComponent(q), {headers:{'Accept':'application/json'}});
            if (!response.ok) return;
            const data = await response.json();
            studentResults.innerHTML = '';
            data.students.forEach(function (student) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'search-item';
                button.textContent = student.name + ' · ' + student.nis + ' · ' + student.class_name;
                button.addEventListener('click', function () {
                    studentId.value = student.student_id;
                    studentInput.value = student.name;
                    selectedStudent.innerHTML = '<strong>' + escapeHtml(student.name) + '</strong><div class="small muted">NIS ' + escapeHtml(student.nis) + ' · ' + escapeHtml(student.class_name) + '</div>';
                    selectedStudent.hidden = false;
                    studentResults.hidden = true;
                });
                studentResults.appendChild(button);
            });
            studentResults.hidden = data.students.length === 0;
        }, 350);
    });

    let itemTimer;
    itemInput.addEventListener('input', function () {
        clearTimeout(itemTimer);
        const q = itemInput.value.trim();
        if (q.length < 2) {
            itemResults.hidden = true;
            return;
        }
        itemTimer = setTimeout(async function () {
            const response = await fetch(itemUrl + '?q=' + encodeURIComponent(q), {headers:{'Accept':'application/json'}});
            if (!response.ok) return;
            const data = await response.json();
            itemResults.innerHTML = '';
            data.items.forEach(function (item) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'search-item';
                button.innerHTML = '<strong>' + escapeHtml(item.name) + '</strong><div class="small muted">Tersedia ' + item.available_qty + (item.specification ? ' · ' + escapeHtml(item.specification) : '') + '</div>';
                button.addEventListener('click', function () {
                    if (!selected.has(item.item_id)) {
                        selected.set(item.item_id, item);
                        renderItems();
                    }
                    itemInput.value = '';
                    itemResults.hidden = true;
                });
                itemResults.appendChild(button);
            });
            itemResults.hidden = data.items.length === 0;
        }, 300);
    });

    function renderItems() {
        selectedItems.innerHTML = '';
        if (selected.size === 0) {
            selectedItems.innerHTML = '<div class="empty">Belum ada barang dipilih.</div>';
            return;
        }

        let index = 0;
        selected.forEach(function (item, id) {
            const row = document.createElement('div');
            row.className = 'item-row';
            row.innerHTML =
                '<div><strong>' + escapeHtml(item.name) + '</strong><div class="small muted">Tersedia ' + item.available_qty + '</div>' +
                '<input type="hidden" name="items[' + index + '][item_id]" value="' + id + '"></div>' +
                '<input class="input" type="number" min="1" max="' + item.available_qty + '" value="1" name="items[' + index + '][quantity]" required>' +
                '<button class="btn danger" type="button">×</button>';
            row.querySelector('button').addEventListener('click', function () {
                selected.delete(id);
                renderItems();
            });
            selectedItems.appendChild(row);
            index++;
        });
    }

    let loanTimer;
    loanInput.addEventListener('input', function () {
        clearTimeout(loanTimer);
        const q = loanInput.value.trim();
        if (q.length < 3) {
            loanResults.hidden = true;
            return;
        }
        loanTimer = setTimeout(async function () {
            const response = await fetch(loanUrl + '?q=' + encodeURIComponent(q), {headers:{'Accept':'application/json'}});
            if (!response.ok) return;
            const data = await response.json();
            loanResults.innerHTML = '';
            data.borrowings.forEach(function (loan) {
                const link = document.createElement('a');
                link.className = 'search-item';
                link.href = transactionBase + '/' + loan.public_id;
                const names = loan.items.map(function (row) { return row.name + ' × ' + row.outstanding_qty; }).join(', ');
                link.innerHTML = '<strong>' + escapeHtml(loan.student_name) + '</strong><div class="small muted">' + escapeHtml(loan.student_class) + ' · ' + escapeHtml(names) + '</div>';
                loanResults.appendChild(link);
            });
            loanResults.hidden = data.borrowings.length === 0;
        }, 350);
    });

    document.getElementById('borrow-form').addEventListener('submit', function (event) {
        if (!studentId.value) {
            event.preventDefault();
            alert('Pilih siswa dari hasil pencarian.');
            return;
        }
        if (selected.size === 0) {
            event.preventDefault();
            alert('Pilih minimal satu barang.');
        }
    });

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, function (char) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char];
        });
    }
})();
</script>
@endsection
