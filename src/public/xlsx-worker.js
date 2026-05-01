importScripts('/vendor/laravel-file-viewer/officetohtml/SheetJS/xlsx.full.min.js');

self.onmessage = function (e) {
  const { buf, rowLimit } = e.data;
  try {
    const wb = XLSX.read(buf, { type: 'array', cellDates: true, dense: true });

    const sheets = {};
    wb.SheetNames.forEach(function (name) {
      const ws  = wb.Sheets[name];
      const aoa = XLSX.utils.sheet_to_json(ws, { header: 1, defval: '' });
      sheets[name] = {
        rows:      aoa.slice(0, rowLimit),
        totalRows: aoa.length,
      };
    });

    self.postMessage({ ok: true, sheetNames: wb.SheetNames, sheets: sheets });
  } catch (err) {
    self.postMessage({ ok: false, error: err.message });
  }
};
