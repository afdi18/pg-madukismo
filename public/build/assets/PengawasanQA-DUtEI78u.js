import{Ct as e,Gt as t,Tt as n,U as r,Wt as i,at as a,b as o,d as s,dt as c,et as l,ft as u,jt as d,lt as f,ot as p,pt as m,qt as h,r as g,rt as _,ut as v,x as y,xt as b,zt as x}from"./vendor-Dz4QZAda.js";import{n as S}from"./app-DyDvJJig.js";import{t as C}from"./_plugin-vue_export-helper-BOai-rQB.js";var w={class:`space-y-6`},T={class:`bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 mb-5 overflow-hidden`},E={class:`px-5 py-4 flex flex-wrap items-center gap-3`},ee={class:`flex items-center gap-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2`},D=[`disabled`],O={key:0,class:`flex flex-col items-center justify-center py-24 gap-4 text-gray-400`},k={key:1,class:`flex flex-col items-center justify-center py-20 gap-3 text-red-400`},A={key:2,class:`bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden`},j={class:`qa-scroll overflow-auto h-screen`},M={class:`border-collapse text-xs w-max min-w-full`},N={class:`hidden md:table-row`},P={class:`md:hidden`},F={class:`sticky left-0 z-20 min-w-[180px] max-w-[180px] bg-gradient-to-r from-gray-800 to-gray-700 dark:from-gray-950 dark:to-gray-900 text-white font-bold text-[11px] tracking-wide px-3 py-2 border border-gray-600 text-left uppercase`},I=[`colspan`],L={class:`px-2 py-1.5 border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 text-center font-bold tabular-nums text-gray-700 dark:text-gray-200`},R={key:3,class:`flex flex-col items-center justify-center py-20 text-gray-400 gap-2`},z=30,B=C(m({__name:`PengawasanQA`,setup(m){let C=x(new Date().toISOString().slice(0,10)),B=x(!1),V=x(!1),H=x(null),U=S(),W=Array.from({length:8},(e,t)=>`${String(t+6).padStart(2,`0`)}-${String(t+7).padStart(2,`0`)}`),G=Array.from({length:8},(e,t)=>`${String(t+14).padStart(2,`0`)}-${String(t+15).padStart(2,`0`)}`),K=a(()=>{let e=[];for(let t=0;t<8;t++){let n=(22+t)%24,r=(22+t+1)%24;e.push(`${String(n).padStart(2,`0`)}-${String(r).padStart(2,`0`)}`)}return e});async function q(){B.value=!0,V.value=!1;try{H.value=(await g.get(`/api/lab-qa/dashboard-monitoring`,{params:{tanggal:C.value}})).data}catch{V.value=!0}finally{B.value=!1}}b(q);function J(e){return e==null?`—`:e%1==0?String(e):e.toFixed(2).replace(/\.?0+$/,``)}function Y(e){return!e||e.nilai===null?`cell-empty`:e.alert?`cell-alert`:`cell-ok`}function X(e,t){return e===null?`cell-empty`:Z(e,t)?`cell-alert`:`cell-ok`}function Z(e,t){if(e===null)return!1;let n=t.operator_kondisi,r=t.batas_bawah,i=t.batas_atas;return n===`>`&&r!==null?e<=r:n===`>=`&&r!==null?e<r:n===`<`&&i!==null?e>=i:n===`<=`&&i!==null?e>i:n===`BETWEEN`&&r!==null&&i!==null?e<r||e>i:!1}function Q(e){return String(e+1).padStart(2,`0`)}function $(e){return e.replaceAll(`&`,`&amp;`).replaceAll(`<`,`&lt;`).replaceAll(`>`,`&gt;`).replaceAll(`"`,`&quot;`)}function te(e){let t=new Date(e);return Number.isNaN(t.getTime())?e:new Intl.DateTimeFormat(`id-ID`,{day:`2-digit`,month:`2-digit`,year:`numeric`}).format(t)}function ne(){let e=H.value?.tanggal?te(H.value.tanggal):`-`,t=new Intl.DateTimeFormat(`id-ID`,{day:`2-digit`,month:`2-digit`,year:`numeric`,hour:`2-digit`,minute:`2-digit`,second:`2-digit`}).format(new Date),n=`${window.location.origin}${window.location.pathname}`,r=U.user?.name||U.user?.username||`Unknown User`,i=Array.from({length:8},(e,t)=>`${String(t+6).padStart(2,`0`)}-${String(t+7).padStart(2,`0`)}`),a=Array.from({length:8},(e,t)=>`${String(t+14).padStart(2,`0`)}-${String(t+15).padStart(2,`0`)}`),o=Array.from({length:8},(e,t)=>{let n=(22+t)%24,r=(22+t+1)%24;return`${String(n).padStart(2,`0`)}-${String(r).padStart(2,`0`)}`}),s=(e,t,n)=>{let r=t.data?.[e];return!r||r.nilai===null?`<td class="empty-cell ${n}">-</td>`:r.alert?`<td class="num alert-cell ${n}">${$(J(r.nilai))}</td>`:`<td class="num ${n}">${$(J(r.nilai))}</td>`},c=e=>e.split(`-`)[0],l=`${`<col class="col-hour" />`.repeat(8)}<col class="col-avg" />`,u=`<colgroup><col class="col-no" /><col class="col-param" />${l}${l}${l}<col class="col-total" /></colgroup>`,d=(H.value?.stasiuns??[]).map((e,t)=>{let n=e.parameters.map((e,t)=>{let n=e.satuan?`<span class="satuan"> (${$(e.satuan)})</span>`:``,r=`<span class="param-name">${$(e.nama_parameter)}${n}</span>`,c=e.ssrn?`<br><span class="ssrn-sub">SSRN: ${$(e.ssrn)}</span>`:``;return`
            <tr class="${t%2==0?`row-odd`:`row-even`}">
              <td class="center">${t+1}</td>
              <td class="param-cell">${r}${c}</td>
              ${i.map(t=>s(t,e,`slot-pagi`)).join(``)}
              <td class="num avg-cell">${$(J(e.pagi_avg))}</td>
              ${a.map(t=>s(t,e,`slot-siang`)).join(``)}
              <td class="num avg-cell">${$(J(e.siang_avg))}</td>
              ${o.map(t=>s(t,e,`slot-malam`)).join(``)}
              <td class="num avg-cell">${$(J(e.malam_avg))}</td>
              <td class="num total-cell">${$(J(e.jml_rt2))}</td>
            </tr>`}).join(``);return`
        <section class="station-block">
          <table>
            ${u}
            <thead>
              <tr>
                <th colspan="30" class="station-title-cell">${Q(t)}. ${$(e.nama_stasiun)}</th>
              </tr>
              <tr>
                <th rowspan="2" class="w-no">No</th>
                <th rowspan="2" class="w-param">Parameter</th>
                <th colspan="9" class="shift-hdr shift-pagi">&#9728; Shift Pagi</th>
                <th colspan="9" class="shift-hdr shift-siang">&#9925; Shift Siang</th>
                <th colspan="9" class="shift-hdr shift-malam">&#9790; Shift Malam</th>
                <th rowspan="2" class="w-total">Jml/<br>Rt2</th>
              </tr>
              <tr>
                ${i.map(e=>`<th class="w-hour hour-pagi">${c(e)}</th>`).join(``)}
                <th class="w-avg avg-hdr">Pagi</th>
                ${a.map(e=>`<th class="w-hour hour-siang">${c(e)}</th>`).join(``)}
                <th class="w-avg avg-hdr">Siang</th>
                ${o.map(e=>`<th class="w-hour hour-malam">${c(e)}</th>`).join(``)}
                <th class="w-avg avg-hdr">Malam</th>
              </tr>
            </thead>
            <tbody>
              ${n||`<tr><td colspan="30" class="empty">Tidak ada data</td></tr>`}
            </tbody>
          </table>
        </section>`}).join(``);return`<!doctype html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Cetak PDF Dashboard Pengawasan QA</title>
  <style>
    @page { size: A4 landscape; margin: 12mm 10mm 10mm 10mm; }
    * {
      box-sizing: border-box;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
      color-adjust: exact;
    }
    body {
      font-family: Arial, Helvetica, sans-serif;
      color: #111827;
      margin: 0;
      font-size: 7.5px;
    }
    .header {
      margin-bottom: 4px;
    }
    .title {
      font-size: 13px;
      font-weight: 700;
      color: #1e3a8a;
      margin: 0;
    }
    .meta {
      margin-top: 2px;
      color: #475569;
      font-size: 8px;
      white-space: normal;
      overflow-wrap: anywhere;
      word-break: break-word;
    }
    .report-divider {
      margin-top: 3px;
      height: 2px;
      background: #1d4ed8;
    }
    .station-block {
      margin-top: 6px;
      page-break-inside: auto;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
      page-break-inside: auto;
    }
    thead { display: table-header-group; }
    col.col-no    { width: 3%; }
    col.col-param { width: 25%; }
    col.col-hour  { width: 2.45%; }
    col.col-avg   { width: 2.8%; }
    col.col-total { width: 3%; }
    thead th {
      background: #e2e8f0;
      color: #334155;
      font-weight: 700;
      text-align: center;
      border: 1px solid #cbd5e1;
      padding: 3px 2px;
      line-height: 1.2;
    }
    thead th.w-no,
    thead th.w-param {
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .station-title-cell {
      text-align: left;
      background: #ffffff;
      border: none;
      color: #1e40af;
      font-size: 9px;
      font-weight: 700;
      padding: 4px 0 4px 0;
    }
    .shift-hdr  { border-bottom: none; }
    .shift-pagi  { background: #fef3c7; color: #92400e; }
    .shift-siang { background: #dbeafe; color: #1e40af; }
    .shift-malam { background: #ede9fe; color: #5b21b6; }
    .hour-pagi   { background: #fffbeb; color: #92400e; }
    .hour-siang  { background: #eff6ff; color: #1e40af; }
    .hour-malam  { background: #f5f3ff; color: #5b21b6; }
    .avg-hdr     { background: #dcfce7; color: #166534; }
    tbody td {
      border: 1px solid #e2e8f0;
      padding: 1px 2px;
      vertical-align: middle;
      white-space: nowrap;
      overflow: hidden;
    }
    tbody td:first-child {
      padding-left: 1px;
      padding-right: 1px;
    }
    tbody tr {
      height: auto;
    }
    tbody tr.row-odd td  { background-color: #ffffff; }
    tbody tr.row-even td { background-color: #f8fafc; }

    .param-cell {
      color: #0f172a;
      white-space: normal;
      overflow: visible;
      word-break: break-word;
      line-height: 1.08;
      padding-top: 1px;
      padding-bottom: 1px;
      font-size: 6.7px;
    }
    .param-name {
      display: block;
      font-weight: 600;
      white-space: normal;
      overflow: visible;
      text-overflow: clip;
      overflow-wrap: anywhere;
      word-break: break-word;
      line-height: 1.12;
    }
    tbody tr.row-odd .param-cell  { background: #f1f5f9; }
    tbody tr.row-even .param-cell { background: #e8eef5; }

    tbody tr.row-odd .slot-pagi  { background: #fffdf2; }
    tbody tr.row-even .slot-pagi { background: #f8f4df; }
    tbody tr.row-odd .slot-siang  { background: #f7fbff; }
    tbody tr.row-even .slot-siang { background: #edf4fc; }
    tbody tr.row-odd .slot-malam  { background: #faf8ff; }
    tbody tr.row-even .slot-malam { background: #f1ecfa; }
    .center    { text-align: center; }
    .num       { text-align: right; font-variant-numeric: tabular-nums; }
    tbody tr.row-odd .avg-cell  { background: #ecfdf3; }
    tbody tr.row-even .avg-cell { background: #e0f7ea; }
    .avg-cell  { color: #166534; font-weight: 600; text-align: right; font-variant-numeric: tabular-nums; }

    tbody tr.row-odd .total-cell  { background: #e8f0ff; }
    tbody tr.row-even .total-cell { background: #dce8ff; }
    .total-cell{ color: #1e3a8a; font-weight: 700; text-align: right; font-variant-numeric: tabular-nums; }
    .alert-cell{ background: #fee2e2 !important; color: #991b1b; font-weight: 600; font-variant-numeric: tabular-nums; }
    .empty-cell{ color: #cbd5e1; text-align: center; }
    .ssrn-sub  {
      color: #6b7280;
      font-size: 5.8px;
      line-height: 1.02;
      display: block;
      margin-top: 1px;
      white-space: normal;
      overflow: visible;
      text-overflow: clip;
      overflow-wrap: anywhere;
      word-break: break-word;
    }
    .satuan    { color: #6b7280; }
    .w-no,
    .w-param,
    .w-hour,
    .w-avg,
    .w-total {
      width: auto;
      min-width: 0;
    }
    .empty   { text-align: center; color: #6b7280; font-style: italic; }
    @media print {
      html, body {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        color-adjust: exact;
      }
      table {
        page-break-inside: auto;
      }
      thead {
        display: table-header-group;
      }
      tbody tr,
      thead tr,
      tfoot tr {
        break-inside: avoid !important;
        page-break-inside: avoid !important;
      }
      td,
      th {
        break-inside: avoid;
        page-break-inside: avoid;
      }
      col.col-no    { width: 3% !important; }
      col.col-param { width: 15% !important; }
      col.col-hour  { width: 3% !important; }
      col.col-avg   { width: 3% !important; }
      col.col-total { width: 3% !important; }
      .header {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        margin: 0;
        z-index: 50;
        background: #ffffff;
        padding-bottom: 3mm;
      }
      .report-divider {
        margin-top: 2px;
      }
      .station-block:first-of-type {
        margin-top: 10mm;
      }

      thead th,
      .shift-pagi,
      .shift-siang,
      .shift-malam,
      .hour-pagi,
      .hour-siang,
      .hour-malam,
      .avg-hdr,
      .param-cell,
      .slot-pagi,
      .slot-siang,
      .slot-malam,
      .avg-cell,
      .total-cell,
      .alert-cell,
      .report-divider {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
      }
      /* Add vertical gap under the repeated table header on every printed page
         so body rows (including continued fragments) start lower. */
      // thead tr:last-child th {
      //   padding-bottom: 2mm;
      // }
    }
  </style>
</head>
<body>
  <div class="header">
    <p class="title">Dashboard Angka Pengawasan QA</p>
    <div class="meta">Tanggal data: ${$(e)} &middot; Dicetak dari: ${$(n)} &middot; Oleh user: ${$(r)} &middot; Tanggal waktu cetak: ${$(t)}</div>
    <div class="report-divider"></div>
  </div>
  ${d||`<p>Tidak ada data untuk dicetak.</p>`}
</body>
</html>`}function re(){if(!H.value)return;let e=ne(),t=new Blob([e],{type:`text/html; charset=utf-8`}),n=URL.createObjectURL(t),r=window.open(n,`_blank`);if(!r){URL.revokeObjectURL(n),alert(`Popup diblokir oleh browser. Harap izinkan popup untuk halaman ini lalu coba lagi.`);return}r.onload=()=>{r.focus(),r.print(),URL.revokeObjectURL(n)}}return(a,m)=>(e(),f(`div`,w,[m[15]||=p(`div`,{class:`mobile-landscape-guard`},[p(`div`,{class:`mobile-landscape-card`},[p(`p`,{class:`text-sm font-semibold text-gray-900 dark:text-gray-100`},`Gunakan mode landscape`),p(`p`,{class:`mt-1 text-xs text-gray-600 dark:text-gray-400`},`Putar perangkat agar dashboard lebih nyaman dilihat.`)])],-1),p(`div`,T,[m[5]||=p(`div`,{class:`h-1 w-full bg-gradient-to-r from-blue-600 via-indigo-500 to-purple-600`},null,-1),p(`div`,E,[m[4]||=p(`div`,{class:`mr-auto`},[p(`h1`,{class:`text-lg font-bold text-gray-900 dark:text-white leading-tight`},` Dashboard Angka Pengawasan QA `),p(`p`,{class:`text-xs text-gray-500 dark:text-gray-400 mt-0.5`},` Monitoring analisa kualitas per jam · semua stasiun `)],-1),p(`div`,ee,[u(i(r),{class:`w-4 h-4 text-gray-400 flex-shrink-0`}),m[1]||=p(`label`,{class:`text-xs font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap`},`Tanggal`,-1),d(p(`input`,{"onUpdate:modelValue":m[0]||=e=>C.value=e,type:`date`,class:`bg-transparent text-sm font-semibold text-gray-800 dark:text-gray-100 outline-none cursor-pointer`,onChange:q},null,544),[[l,C.value]])]),p(`button`,{class:`flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white text-sm font-semibold rounded-xl shadow-sm transition-all duration-150`,disabled:B.value,onClick:q},[u(i(o),{class:t([`w-4 h-4`,{"animate-spin":B.value}])},null,8,[`class`]),m[2]||=c(` Refresh `,-1)],8,D),p(`button`,{class:`flex items-center gap-2 px-4 py-2 bg-gray-700 hover:bg-gray-800 active:scale-95 text-white text-sm font-semibold rounded-xl shadow-sm transition-all duration-150 print:hidden`,onClick:re},[u(i(y),{class:`w-4 h-4`}),m[3]||=c(` Cetak PDF `,-1)])])]),B.value?(e(),f(`div`,O,[u(i(o),{class:`w-8 h-8 animate-spin text-indigo-400`}),m[6]||=p(`span`,{class:`text-sm`},`Memuat data…`,-1)])):V.value?(e(),f(`div`,k,[u(i(s),{class:`w-8 h-8`}),m[7]||=p(`p`,{class:`text-sm font-medium`},`Gagal memuat data. Periksa koneksi atau coba refresh.`,-1)])):H.value?(e(),f(`div`,A,[m[13]||=v(`<div class="flex flex-wrap items-center gap-4 px-5 py-3 border-b border-gray-100 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-800/30 print:hidden" data-v-2f2cd9cc><span class="text-xs text-gray-500 font-medium" data-v-2f2cd9cc>Keterangan :</span><span class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-300" data-v-2f2cd9cc><span class="inline-block w-3 h-3 rounded bg-emerald-200 dark:bg-emerald-800" data-v-2f2cd9cc></span> Dalam batas </span><span class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-300" data-v-2f2cd9cc><span class="inline-block w-3 h-3 rounded bg-red-200 dark:bg-red-900" data-v-2f2cd9cc></span> Di luar batas (alert) </span><span class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-300" data-v-2f2cd9cc><span class="inline-block w-3 h-3 rounded bg-gray-100 dark:bg-gray-700 border border-dashed border-gray-300 dark:border-gray-600" data-v-2f2cd9cc></span> Belum ada data </span></div>`,1),p(`div`,j,[p(`table`,M,[p(`thead`,null,[m[11]||=p(`tr`,null,[p(`th`,{rowspan:`2`,class:`sticky top-0 left-0 z-40 min-w-[180px] max-w-[180px] bg-gray-800 dark:bg-gray-950 text-white border border-gray-700 px-3 py-2.5 text-left font-semibold text-xs tracking-wide`},` PARAMETER `),p(`th`,{rowspan:`2`,class:`sticky top-0 left-[180px] z-40 min-w-[72px] max-w-[72px] bg-gray-800 dark:bg-gray-950 text-white border border-gray-700 px-2 py-2.5 text-center font-semibold text-xs tracking-wide`},` SSRN `),p(`th`,{colspan:`9`,class:`sticky top-0 z-30 bg-sky-600 text-white border border-sky-700 px-3 py-2 text-center font-bold text-xs tracking-widest uppercase`},` ☀ Shift Pagi `),p(`th`,{colspan:`9`,class:`sticky top-0 z-30 bg-amber-500 text-white border border-amber-600 px-3 py-2 text-center font-bold text-xs tracking-widest uppercase`},` ⛅ Shift Siang `),p(`th`,{colspan:`9`,class:`sticky top-0 z-30 bg-indigo-700 text-white border border-indigo-800 px-3 py-2 text-center font-bold text-xs tracking-widest uppercase`},` 🌙 Shift Malam `),p(`th`,{rowspan:`2`,class:`sticky top-0 z-30 bg-gray-700 text-white border border-gray-600 px-2 py-2.5 text-center font-bold text-xs`},[c(` Jml/`),p(`br`),c(`Rt2 `)])],-1),p(`tr`,null,[(e(!0),f(_,null,n(i(W),t=>(e(),f(`th`,{key:`h-`+t,class:`sticky top-[36px] z-20 bg-sky-50 dark:bg-sky-950 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800 px-1.5 py-1.5 text-center font-semibold min-w-[48px] whitespace-nowrap`},h(t),1))),128)),m[8]||=p(`th`,{class:`sticky top-[36px] z-20 bg-sky-600/90 text-white border border-sky-700 px-2 py-1.5 text-center font-bold min-w-[44px]`},` Pagi `,-1),(e(!0),f(_,null,n(i(G),t=>(e(),f(`th`,{key:`h-`+t,class:`sticky top-[36px] z-20 bg-amber-50 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 px-1.5 py-1.5 text-center font-semibold min-w-[48px] whitespace-nowrap`},h(t),1))),128)),m[9]||=p(`th`,{class:`sticky top-[36px] z-20 bg-amber-500/90 text-white border border-amber-600 px-2 py-1.5 text-center font-bold min-w-[44px]`},` Siang `,-1),(e(!0),f(_,null,n(K.value,t=>(e(),f(`th`,{key:`h-`+t,class:`sticky top-[36px] z-20 bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 px-1.5 py-1.5 text-center font-semibold min-w-[48px] whitespace-nowrap`},h(t),1))),128)),m[10]||=p(`th`,{class:`sticky top-[36px] z-20 bg-indigo-700/90 text-white border border-indigo-800 px-2 py-1.5 text-center font-bold min-w-[44px]`},` Malam `,-1)])]),p(`tbody`,null,[(e(!0),f(_,null,n(H.value.stasiuns,(r,a)=>(e(),f(_,{key:r.id},[p(`tr`,N,[p(`td`,{colspan:z,class:`bg-gradient-to-r from-gray-800 to-gray-700 dark:from-gray-950 dark:to-gray-900 text-white font-bold text-xs tracking-widest px-4 py-2 border border-gray-600 text-center uppercase`},h(Q(a))+`. `+h(r.nama_stasiun.toUpperCase()),1)]),p(`tr`,P,[p(`td`,F,h(Q(a))+`. `+h(r.nama_stasiun),1),m[12]||=p(`td`,{class:`sticky left-[180px] z-20 min-w-[72px] max-w-[72px] bg-gradient-to-r from-gray-800 to-gray-700 dark:from-gray-950 dark:to-gray-900 border border-gray-600`},null,-1),p(`td`,{colspan:z-2,class:`bg-gradient-to-r from-gray-800 to-gray-700 dark:from-gray-950 dark:to-gray-900 border border-gray-600`},null,8,I)]),(e(!0),f(_,null,n(r.parameters,(r,a)=>(e(),f(`tr`,{key:r.id,class:t([a%2==0?`bg-white dark:bg-gray-900`:`bg-gray-50/80 dark:bg-gray-800/40`,`hover:bg-blue-50/40 dark:hover:bg-blue-900/10 transition-colors`])},[p(`td`,{class:t([`sticky left-0 z-10 min-w-[180px] max-w-[180px] px-3 py-1.5 border border-gray-200 dark:border-gray-700 font-medium text-gray-700 dark:text-gray-300 bg-inherit whitespace-nowrap overflow-hidden text-ellipsis`,a%2==0?`bg-white dark:bg-gray-900`:`bg-gray-50 dark:bg-gray-800`])},h(r.nama_parameter),3),p(`td`,{class:t([`sticky left-[180px] z-10 min-w-[72px] max-w-[72px] px-2 py-1.5 border border-gray-200 dark:border-gray-700 text-center font-semibold text-gray-600 dark:text-gray-400 whitespace-nowrap`,a%2==0?`bg-white dark:bg-gray-900`:`bg-gray-50 dark:bg-gray-800`])},h(r.ssrn),3),(e(!0),f(_,null,n(i(W),n=>(e(),f(`td`,{key:`p-`+n,class:t([`px-1.5 py-1.5 border border-gray-100 dark:border-gray-800 text-center min-w-[48px] tabular-nums transition-colors`,Y(r.data[n])])},h(J(r.data[n]?.nilai??null)),3))),128)),p(`td`,{class:t([`px-2 py-1.5 border border-sky-200 dark:border-sky-800 text-center font-bold min-w-[44px] tabular-nums`,X(r.pagi_avg,r)])},h(J(r.pagi_avg)),3),(e(!0),f(_,null,n(i(G),n=>(e(),f(`td`,{key:`s-`+n,class:t([`px-1.5 py-1.5 border border-gray-100 dark:border-gray-800 text-center min-w-[48px] tabular-nums transition-colors`,Y(r.data[n])])},h(J(r.data[n]?.nilai??null)),3))),128)),p(`td`,{class:t([`px-2 py-1.5 border border-amber-200 dark:border-amber-800 text-center font-bold min-w-[44px] tabular-nums`,X(r.siang_avg,r)])},h(J(r.siang_avg)),3),(e(!0),f(_,null,n(K.value,n=>(e(),f(`td`,{key:`m-`+n,class:t([`px-1.5 py-1.5 border border-gray-100 dark:border-gray-800 text-center min-w-[48px] tabular-nums transition-colors`,Y(r.data[n])])},h(J(r.data[n]?.nilai??null)),3))),128)),p(`td`,{class:t([`px-2 py-1.5 border border-indigo-200 dark:border-indigo-800 text-center font-bold min-w-[44px] tabular-nums`,X(r.malam_avg,r)])},h(J(r.malam_avg)),3),p(`td`,L,h(J(r.jml_rt2)),1)],2))),128))],64))),128))])])])])):(e(),f(`div`,R,[u(i(r),{class:`w-8 h-8`}),m[14]||=p(`p`,{class:`text-sm`},`Pilih tanggal untuk memuat data.`,-1)]))]))}}),[[`__scopeId`,`data-v-2f2cd9cc`]]);export{B as default};