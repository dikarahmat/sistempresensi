@extends('layouts.app')

@section('title', __('Rekap Presensi Guru'))

@section('page_title', __('Rekap Presensi Guru'))

@section('canvas_class', 'page-canvas-fixed')

@push('styles')
<style>
</style>
@endpush

@section('content')
<div class="flex items-start gap-4">
    <div class="relative flex-1">
        <button id="modeToggleBtn" class="w-full inline-flex items-center justify-between rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:border-blue-500 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
            <span>Mode Siswa</span>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10h4a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2zm11 5a2 2 0 0 1-4 0 2 2 0 0 1 4 0z"/></svg>
        </button>
        
        <input type="hidden" id="currentMode" value="siswa">
        
        <div id="modeMenu" class="hidden absolute right-0 mt-2 w-56 rounded-md bg-white shadow-lg border border-gray-200 z-10">
            <button mode="siswa" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Mode Siswa</button>
            <button mode="guru" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Mode Guru</button>
        </div>
    </div>
</div>

<div id="guruRecapContent" class="mt-4"></div>
</div>

<script>
    document.getElementById('modeToggleBtn').addEventListener('click', function(e) {
        e.stopPropagation();
        var menu = document.getElementById('modeMenu');
        var isHidden = menu.style.display === 'none' || menu.style.display === '';
        menu.style.display = isHidden ? 'block' : 'none';
    });
    
    document.querySelectorAll('#modeMenu button').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var mode = this.getAttribute('mode');
            document.getElementById('currentMode').value = mode;
            
            var btnText = document.querySelector('#modeToggleBtn span');
            var icon = document.querySelector('#modeToggleBtn svg');
            if (mode === 'guru') {
                btnText.textContent = 'Mode Guru';
                icon.innerHTML = '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7-7-7"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10h4a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2zm14-6v6a2 2 0 0 1-2 2H16a2 2 0 0 1-2-2v-6m0 0h-4m0 0v-4m0 0h4m6-4a2 2 0 1 1-4 0 2 2 0 0 1 4 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v4z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l4-4 4 4m0 0l-4-4-4 4"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14H5a2 2 0 0 1-2-2m4 4a2 2 0 0 1 2 2m-4-4a2 2 0 0 1-2-2"/></svg>';
            } else {
                btnText.textContent = 'Mode Siswa';
                icon.innerHTML = '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10h4a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2zm11 5a2 2 0 0 1-4 0 2 2 0 0 1 4 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l4-4 4 4m0 0l-4-4-4 4"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v4z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14H5a2 2 0 0 1-2-2m4 4a2 2 0 0 1 2 2m-4-4a2 2 0 0 1-2-2"/></svg>';
            }
            
            loadRecapContent(mode);
        });
    });
    
    function loadRecapContent(mode) {
        var contentDiv = document.getElementById('guruRecapContent');
        contentDiv.innerHTML = '<div class="py-8 text-center"><div class="spinner spinner-sm mr-2"></div>Loading...</div>';
        
        fetch('/admin/rekap/guru/data?mode=' + mode)
            .then(response => response.json())
            .then(function(data) {
                if (data.html) {
                    contentDiv.innerHTML = data.html;
                } else {
                    contentDiv.innerHTML = '<p class="text-red-600">Error loading content: ' + (data.message || 'Unknown error') + '</p>';
                }
            })
            .catch(function(error) {
                contentDiv.innerHTML = '<p class="text-red-600">Error loading content: ' + error.message + '</p>';
            });
    }
    
    function exportGuruPdf() {
        var mode = document.getElementById('currentMode').value;
        window.location.href = '/admin/rekap/guru/pdf?mode=' + mode;
    }
    
    function exportGuruExcel() {
        var mode = document.getElementById('currentMode').value;
        window.location.href = '/admin/rekap/guru/excel?mode=' + mode;
    }
</script>
@endsection

@push('scripts')
<script>
</script>
@endpush