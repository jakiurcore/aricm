(function () {
  const BN = '০১২৩৪৫৬৭৮৯';
  const bn = v => String(v).replace(/[0-9]/g, d => BN[d]);
  const group = n => { const s = String(Math.round(Math.abs(n))); if (s.length <= 3) return s; const last = s.slice(-3), rest = s.slice(0, -3); return rest.replace(/\B(?=(\d{2})+(?!\d))/g, ',') + ',' + last; };
  const tk = n => (n < 0 ? '−' : '') + '৳' + bn(group(n));
  const W = 'শূন্য এক দুই তিন চার পাঁচ ছয় সাত আট নয় দশ এগারো বারো তেরো চৌদ্দ পনেরো ষোল সতেরো আঠারো উনিশ বিশ একুশ বাইশ তেইশ চব্বিশ পঁচিশ ছাব্বিশ সাতাশ আটাশ ঊনত্রিশ ত্রিশ একত্রিশ বত্রিশ তেত্রিশ চৌত্রিশ পঁয়ত্রিশ ছত্রিশ সাঁইত্রিশ আটত্রিশ ঊনচল্লিশ চল্লিশ একচল্লিশ বিয়াল্লিশ তেতাল্লিশ চুয়াল্লিশ পঁয়তাল্লিশ ছেচল্লিশ সাতচল্লিশ আটচল্লিশ ঊনপঞ্চাশ পঞ্চাশ একান্ন বায়ান্ন তিপ্পান্ন চুয়ান্ন পঞ্চান্ন ছাপ্পান্ন সাতান্ন আটান্ন ঊনষাট ষাট একষট্টি বাষট্টি তেষট্টি চৌষট্টি পঁয়ষট্টি ছেষট্টি সাতষট্টি আটষট্টি ঊনসত্তর সত্তর একাত্তর বাহাত্তর তিয়াত্তর চুয়াত্তর পঁচাত্তর ছিয়াত্তর সাতাত্তর আটাত্তর ঊনআশি আশি একাশি বিরাশি তিরাশি চুরাশি পঁচাশি ছিয়াশি সাতাশি আটাশি ঊননব্বই নব্বই একানব্বই বিরানব্বই তিরানব্বই চুরানব্বই পঁচানব্বই ছিয়ানব্বই সাতানব্বই আটানব্বই নিরানব্বই'.split(' ');
  const words = n => {
    n = Math.round(n); if (!n) return 'শূন্য টাকা মাত্র';
    const parts = []; const cr = Math.floor(n / 1e7); n %= 1e7; const lk = Math.floor(n / 1e5); n %= 1e5; const th = Math.floor(n / 1000); n %= 1000; const hu = Math.floor(n / 100); n %= 100;
    if (cr) parts.push(W[cr] + ' কোটি'); if (lk) parts.push(W[lk] + ' লক্ষ'); if (th) parts.push(W[th] + ' হাজার'); if (hu) parts.push(W[hu] + 'শত'); if (n) parts.push(W[n]);
    return parts.join(' ') + ' টাকা মাত্র';
  };
  const MB = ['জানুয়ারি','ফেব্রুয়ারি','মার্চ','এপ্রিল','মে','জুন','জুলাই','আগস্ট','সেপ্টেম্বর','অক্টোবর','নভেম্বর','ডিসেম্বর'];
  const date = iso => { const [y, m, d] = iso.split('-').map(Number); return `${bn(d)} ${MB[m - 1]} ${bn(y)}`; };
  const dateShort = iso => { const [, m, d] = iso.split('-').map(Number); return `${bn(d)} ${MB[m - 1]}`; };
  const today = '2026-10-18', session = 2026;

  const settings = { dueDay: 10, fine: 50, fineAfter: 5, autoGen: true, gateway: false, blockAdmit: true, blockResult: false,
    receiptFooter: 'এই রসিদটি কম্পিউটারে তৈরি। QR কোড স্ক্যান করে যাচাই করুন। আল্লাহ আপনার সন্তানের জ্ঞান বৃদ্ধি করুন।',
    wallets: [
      { id: 'bkash', name: 'বিকাশ', en: 'bKash', number: '01714844346', type: 'মার্চেন্ট', color: '#E2136E', how: 'বিকাশ অ্যাপ → পেমেন্ট → মার্চেন্ট নম্বর দিন → টাকার পরিমাণ → রেফারেন্সে স্টুডেন্ট আইডি' },
      { id: 'nagad', name: 'নগদ', en: 'Nagad', number: '01761781338', type: 'পার্সোনাল', color: '#EC1C24', how: 'নগদ অ্যাপ → সেন্ড মানি → নম্বর দিন → টাকার পরিমাণ → রেফারেন্সে স্টুডেন্ট আইডি' },
      { id: 'rocket', name: 'রকেট', en: 'Rocket', number: '017148443463', type: 'পার্সোনাল', color: '#8C3494', how: '*৩২২# ডায়াল → সেন্ড মানি → নম্বর দিন → টাকার পরিমাণ → রেফারেন্সে স্টুডেন্ট আইডি' },
    ] };
  const methods = { cash: { bn: 'নগদ (অফিস)', icon: '৳', color: '#0B6B3A' }, bkash: { bn: 'বিকাশ', icon: 'b', color: '#E2136E' }, nagad: { bn: 'নগদ', icon: 'N', color: '#EC1C24' }, rocket: { bn: 'রকেট', icon: 'R', color: '#8C3494' }, online: { bn: 'অনলাইন', icon: '⌁', color: '#16307A' } };

  const heads = [
    { id: 'tuition', bn: 'মাসিক বেতন', en: 'Monthly tuition', type: 'monthly', active: true },
    { id: 'residential', bn: 'আবাসিক ফি', en: 'Residential fee', type: 'monthly', active: true },
    { id: 'meal', bn: 'খাবার ফি', en: 'Meal fee', type: 'monthly', active: true },
    { id: 'daycare', bn: 'ডে-কেয়ার ফি', en: 'Day-care fee', type: 'monthly', active: true },
    { id: 'abacus', bn: 'অ্যাবাকাস ফি', en: 'Abacus fee', type: 'monthly', active: true },
    { id: 'admission', bn: 'ভর্তি ফি', en: 'Admission fee', type: 'onetime', active: true },
    { id: 'session', bn: 'সেশন ফি', en: 'Session fee', type: 'onetime', active: true },
    { id: 'books', bn: 'বই-খাতা', en: 'Books & stationery', type: 'onetime', active: true },
    { id: 'exam', bn: 'পরীক্ষার ফি', en: 'Exam fee', type: 'exam', active: true },
    { id: 'transport', bn: 'পরিবহন ফি', en: 'Transport fee', type: 'monthly', active: false },
  ];
  const head = id => heads.find(h => h.id === id);
  const typeBn = { monthly: 'মাসিক', onetime: 'এককালীন', exam: 'পরীক্ষা' };
  const residencies = [{ id: 'day', bn: 'অনাবাসিক' }, { id: 'res', bn: 'আবাসিক' }, { id: 'care', bn: 'ডে-কেয়ার' }];
  const structCols = ['tuition', 'residential', 'meal', 'daycare', 'abacus', 'admission', 'session', 'books'];
  const tuitionBy = [800, 800, 900, 1000, 1200, 1300, 1400, 1500];
  const booksBy = [1200, 1300, 1400, 1600, 1800, 2000, 2100, 2200];
  const structure = {};
  ['c1','c2','c3','c4','c5','c6','c7','c8'].forEach((c, i) => residencies.forEach(r => {
    structure[`${session}-${c}-${r.id}`] = { tuition: tuitionBy[i], residential: r.id === 'res' ? 2500 : 0, meal: r.id === 'res' ? 3500 : r.id === 'care' ? 1200 : 0, daycare: r.id === 'care' ? 1000 : 0,
      abacus: i < 6 ? 500 : 0, admission: 5000, session: 3000, books: booksBy[i] };
    structure[`${session - 1}-${c}-${r.id}`] = Object.fromEntries(Object.entries(structure[`${session}-${c}-${r.id}`]).map(([k, v]) => [k, Math.round(v * 0.92 / 50) * 50]));
  }));

  const concessions = [
    { id: 'cn1', stu: '20240101', name: 'মোহাম্মদ আব্দুল্লাহ আল মামুন', cls: 'c5', head: 'tuition', kind: 'pct', value: 10, reason: 'ভাই-বোন', from: '2026-01', to: '2026-12', by: 'অধ্যক্ষ' },
    { id: 'cn2', stu: '20240105', name: 'তাহমিদ হাসান', cls: 'c5', head: 'tuition', kind: 'pct', value: 100, reason: 'এতিম', from: '2026-01', to: '2026-12', by: 'অধ্যক্ষ' },
    { id: 'cn3', stu: '20240110', name: 'মারিয়াম বিনতে হাসান', cls: 'c5', head: 'tuition', kind: 'pct', value: 50, reason: 'মেধা বৃত্তি', from: '2026-01', to: '2026-06', by: 'পরিচালনা কমিটি' },
    { id: 'cn4', stu: '20240214', name: 'উমর ফারুক', cls: 'c6', head: 'meal', kind: 'fixed', value: 1000, reason: 'বিশেষ বিবেচনা', from: '2026-07', to: '2026-12', by: 'অধ্যক্ষ' },
    { id: 'cn5', stu: '20240317', name: 'তাসনিম জাহান', cls: 'c3', head: 'abacus', kind: 'fixed', value: 250, reason: 'ভাই-বোন', from: '2026-01', to: '2026-12', by: 'হিসাবরক্ষক' },
    { id: 'cn6', stu: '20240408', name: 'সুমাইয়া আক্তার', cls: 'c4', head: 'admission', kind: 'pct', value: 50, reason: 'এতিম', from: '2026-01', to: '2026-01', by: 'অধ্যক্ষ' },
  ];
  const reasons = ['এতিম', 'ভাই-বোন', 'মেধা বৃত্তি', 'বিশেষ বিবেচনা'];

  // the demo student's invoices
  const monthly = [{ head: 'tuition', amt: 1200 }, { head: 'abacus', amt: 500 }];
  const mDisc = 120;
  const pad = m => String(m).padStart(2, '0');
  const invoices = [];
  for (let m = 1; m <= 12; m++) {
    const st = m === 3 ? 'waived' : m <= 8 ? 'paid' : m === 9 ? 'partial' : m === 10 ? 'due' : 'upcoming';
    const net = 1700 - mDisc, fine = m === 10 ? 50 : 0;
    invoices.push({ id: `INV-26${pad(m)}-0112`, kind: 'monthly', month: m, title: `${MB[m - 1]} ${bn(session)} — মাসিক বেতন`, issue: `${session}-${pad(m)}-01`, due: `${session}-${pad(m)}-10`,
      items: monthly, discount: mDisc, discNote: 'ভাই-বোন ছাড় (বেতনের ১০%)', fine, total: st === 'waived' ? 0 : net + fine,
      paid: st === 'paid' ? net : st === 'partial' ? 1000 : 0, status: st, waiveNote: m === 3 ? 'রমজান মাস — পরিচালনা কমিটির সিদ্ধান্তে মওকুফ' : '' });
  }
  invoices.push(
    { id: 'INV-26S-0112', kind: 'other', title: `সেশন ফি ${bn(session)}`, issue: '2026-01-01', due: '2026-01-15', items: [{ head: 'session', amt: 3000 }], discount: 0, fine: 0, total: 3000, paid: 3000, status: 'paid' },
    { id: 'INV-26B-0112', kind: 'other', title: 'বই-খাতা', issue: '2026-01-01', due: '2026-01-15', items: [{ head: 'books', amt: 1800 }], discount: 0, fine: 0, total: 1800, paid: 1800, status: 'paid' },
    { id: 'INV-26E1-0112', kind: 'other', title: 'অর্ধবার্ষিক পরীক্ষার ফি', issue: '2026-05-20', due: '2026-06-10', items: [{ head: 'exam', amt: 400 }], discount: 0, fine: 0, total: 400, paid: 400, status: 'paid' },
    { id: 'INV-26E2-0112', kind: 'other', title: 'বার্ষিক পরীক্ষার ফি', issue: '2026-10-15', due: '2026-11-15', items: [{ head: 'exam', amt: 500 }], discount: 0, fine: 0, total: 500, paid: 0, status: 'due' },
  );
  const payments = [
    { id: 'RCPT-26-0141', date: '2026-01-08', method: 'cash', amount: 6380, txn: '', sender: '', inv: ['INV-2601-0112', 'INV-26S-0112', 'INV-26B-0112'], status: 'approved', by: 'মোঃ জাকির হোসেন' },
    { id: 'RCPT-26-0388', date: '2026-02-09', method: 'bkash', amount: 1580, txn: '9BX7KQ2M1A', sender: '01712345678', inv: ['INV-2602-0112'], status: 'approved', by: 'অনলাইন যাচাই' },
    { id: 'RCPT-26-0702', date: '2026-04-07', method: 'cash', amount: 1580, txn: '', sender: '', inv: ['INV-2604-0112'], status: 'approved', by: 'মোঃ জাকির হোসেন' },
    { id: 'RCPT-26-0915', date: '2026-05-10', method: 'nagad', amount: 1580, txn: '71ND4K9QZ', sender: '01712345678', inv: ['INV-2605-0112'], status: 'approved', by: 'অনলাইন যাচাই' },
    { id: 'RCPT-26-1120', date: '2026-06-08', method: 'bkash', amount: 1980, txn: '9FH2LM8W3C', sender: '01712345678', inv: ['INV-2606-0112', 'INV-26E1-0112'], status: 'approved', by: 'অনলাইন যাচাই' },
    { id: 'RCPT-26-1344', date: '2026-07-09', method: 'cash', amount: 1580, txn: '', sender: '', inv: ['INV-2607-0112'], status: 'approved', by: 'মোসাঃ শাহনাজ পারভীন' },
    { id: 'RCPT-26-1561', date: '2026-08-06', method: 'rocket', amount: 1580, txn: 'RKT55821904', sender: '017123456785', inv: ['INV-2608-0112'], status: 'approved', by: 'অনলাইন যাচাই' },
    { id: 'PAY-26-1688', date: '2026-08-28', method: 'nagad', amount: 1580, txn: '71NX0000QQ', sender: '01712345678', inv: ['INV-2609-0112'], status: 'rejected', reason: 'ট্রানজেকশন আইডি মেলেনি — মাদরাসার নম্বরে এই লেনদেন পাওয়া যায়নি', by: '' },
    { id: 'RCPT-26-1702', date: '2026-09-12', method: 'bkash', amount: 1000, txn: '9KD3TT6Y7P', sender: '01712345678', inv: ['INV-2609-0112'], status: 'approved', by: 'অনলাইন যাচাই' },
  ];

  // admin: collection desk roster
  const names = [['01','আব্দুর রহমান'],['02','আয়েশা সিদ্দিকা'],['03','মুহাম্মদ ইব্রাহিম'],['04','ফাতেমা তুজ জোহরা'],['05','তাহমিদ হাসান'],['06','নুসাইবা ইসলাম'],['07','ইয়াসিন আরাফাত'],['08','সুমাইয়া আক্তার'],['09','রাফসান জানি'],['10','মারিয়াম বিনতে হাসান'],['11','আহনাফ তাহমিদ'],['12','মোহাম্মদ আব্দুল্লাহ আল মামুন'],['13','হাফসা খাতুন'],['14','উমর ফারুক']];
  const desk = names.map(([roll, name], i) => {
    const id = roll === '12' ? '20240101' : '202401' + roll;
    const dueMonths = roll === '12' ? [9, 10] : [[], [10], [9, 10], [], [10], [8, 9, 10], [10], [], [10], [], [7, 8, 9, 10], [], [10], [9, 10]][i];
    const net = roll === '05' ? 500 : roll === '10' ? 1100 : 1700;
    return { id, roll, name, cls: 'c5', sec: 'A', phone: '0171' + String(2345600 + i * 37).slice(0, 7), father: ['মোঃ আব্দুল করিম','মোঃ শহিদুল ইসলাম','মোঃ রফিকুল ইসলাম','মোঃ আজিজুল হক'][i % 4], res: i % 5 === 3 ? 'res' : 'day', net, dueMonths };
  });
  const verifyQ = [
    { id: 'PAY-26-2011', stu: '20240107', name: 'ইয়াসিন আরাফাত', cls: '৫ম শ্রেণি – ক', amount: 1700, method: 'bkash', txn: '9QW3ER5TY7', sender: '01712345938', at: '2026-10-18 08:42', months: 'অক্টোবর', shot: true },
    { id: 'PAY-26-2009', stu: '20240311', name: 'সাদিয়া আফরিন', cls: '৩য় শ্রেণি – খ', amount: 1800, method: 'nagad', txn: '71NC8D2KQ', sender: '01822441190', at: '2026-10-17 21:15', months: 'সেপ্টেম্বর, অক্টোবর', shot: true },
    { id: 'PAY-26-2004', stu: '20240702', name: 'আবু তালহা', cls: '৭ম শ্রেণি – ক', amount: 7400, method: 'rocket', txn: 'RKT77120456', sender: '019114488221', at: '2026-10-17 19:03', months: 'অক্টোবর (আবাসিক)', shot: false },
    { id: 'PAY-26-1998', stu: '20240115', name: 'জান্নাতুল ফেরদৌস', cls: '৫ম শ্রেণি – ক', amount: 500, method: 'bkash', txn: '9LM0PO8IU2', sender: '01755123987', at: '2026-10-16 14:30', months: 'বার্ষিক পরীক্ষার ফি', shot: true },
    { id: 'PAY-26-1990', stu: '20240804', name: 'মাহমুদুল হাসান', cls: '৮ম শ্রেণি – ক', amount: 2000, method: 'online', txn: 'SSL2610160087', sender: '—', at: '2026-10-16 10:12', months: 'অক্টোবর', shot: false },
  ];
  const rejectReasons = ['ট্রানজেকশন আইডি মেলেনি', 'টাকার পরিমাণ মেলেনি', 'একই লেনদেন আগে জমা হয়েছে', 'স্ক্রিনশট অস্পষ্ট'];

  // reports
  const clsBn = ['১ম শ্রেণি','২য় শ্রেণি','৩য় শ্রেণি','৪র্থ শ্রেণি','৫ম শ্রেণি','৬ষ্ঠ শ্রেণি','৭ম শ্রেণি','৮ম শ্রেণি'];
  const byClass = clsBn.map((c, i) => { const stu = [42, 40, 38, 36, 35, 33, 30, 28][i]; const exp = stu * (tuitionBy[i] + (i < 6 ? 500 : 0)) + [3, 4, 5, 6, 7, 9, 11, 12][i] * 6000; const col = Math.round(exp * [0.86, 0.78, 0.81, 0.72, 0.69, 0.74, 0.63, 0.58][i] / 10) * 10; return { cls: c, stu, exp, col, due: exp - col, dueStu: Math.round(stu * (1 - col / exp) * 1.6) }; });
  const cashBook = [
    ['09:12', 'RCPT-26-2101', 'আয়েশা সিদ্দিকা', '৫ম – ক', 'মাসিক বেতন (অক্টোবর)', 'cash', 1700, 'মোঃ জাকির হোসেন'],
    ['09:40', 'RCPT-26-2102', 'আবু তালহা', '৭ম – ক', 'আবাসিক + খাবার (অক্টোবর)', 'cash', 7400, 'মোঃ জাকির হোসেন'],
    ['10:05', 'RCPT-26-2103', 'নাফিসা তাবাসসুম', '২য় – ক', 'মাসিক বেতন (সেপ্টে–অক্টো)', 'cash', 2600, 'মোসাঃ শাহনাজ পারভীন'],
    ['10:48', 'RCPT-26-2104', 'ইয়াসিন আরাফাত', '৫ম – ক', 'মাসিক বেতন (অক্টোবর)', 'bkash', 1700, 'অনলাইন যাচাই'],
    ['11:30', 'RCPT-26-2105', 'রাইয়ান হোসেন', '৮ম – খ', 'বার্ষিক পরীক্ষার ফি', 'cash', 600, 'মোসাঃ শাহনাজ পারভীন'],
    ['12:15', 'RCPT-26-2106', 'হাফসা খাতুন', '৫ম – ক', 'মাসিক বেতন (অক্টোবর)', 'nagad', 1700, 'অনলাইন যাচাই'],
    ['01:50', 'RCPT-26-2107', 'সাদ বিন আলম', '১ম – খ', 'মাসিক বেতন + অ্যাবাকাস', 'cash', 1300, 'মোঃ জাকির হোসেন'],
  ];
  const headWise = [['tuition', 312400], ['residential', 87500], ['meal', 122500], ['abacus', 61500], ['daycare', 14000], ['exam', 38600], ['session', 21000], ['books', 9600]];
  const collectors = [['মোঃ জাকির হোসেন', 'হিসাবরক্ষক', 146, 268900], ['মোসাঃ শাহনাজ পারভীন', 'অফিস সহকারী', 98, 171300], ['অনলাইন যাচাই', 'বিকাশ / নগদ / রকেট', 112, 187400]];

  window.FE = { bn, tk, words, group, MB, date, dateShort, today, session, settings, methods, heads, head, typeBn, residencies, structCols, structure, concessions, reasons,
    invoices, payments, desk, verifyQ, rejectReasons, byClass, cashBook, headWise, collectors, clsBn };
})();
