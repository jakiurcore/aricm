(function () {
  const BN = '০১২৩৪৫৬৭৮৯';
  const bn = v => String(v).replace(/[0-9]/g, d => BN[d]);
  const MB = ['জানুয়ারি','ফেব্রুয়ারি','মার্চ','এপ্রিল','মে','জুন','জুলাই','আগস্ট','সেপ্টেম্বর','অক্টোবর','নভেম্বর','ডিসেম্বর'];
  const DB = ['রবিবার','সোমবার','মঙ্গলবার','বুধবার','বৃহস্পতিবার','শুক্রবার','শনিবার'];
  const DS = ['রবি','সোম','মঙ্গল','বুধ','বৃহঃ','শুক্র','শনি'];
  const P = iso => { const [y, m, d] = iso.split('-').map(Number); return { y, m, d, dow: new Date(Date.UTC(y, m - 1, d)).getUTCDay() }; };
  const add = (iso, n) => { const d = new Date(iso + 'T00:00:00Z'); d.setUTCDate(d.getUTCDate() + n); return d.toISOString().slice(0, 10); };
  const date = iso => { const p = P(iso); return `${bn(p.d)} ${MB[p.m - 1]} ${bn(p.y)}`; };
  const dateShort = iso => { const p = P(iso); return `${bn(p.d)} ${MB[p.m - 1]}`; };
  const dayName = iso => DB[P(iso).dow];
  const dayShort = iso => DS[P(iso).dow];
  const time = hm => { let [h, m] = hm.split(':').map(Number); const pm = h >= 12; h = h % 12 || 12; return `${bn(h)}:${bn(String(m).padStart(2, '0'))} ${pm ? 'PM' : 'AM'}`; };

  const today = '2026-10-18';
  const settings = {
    editUntil: '23:59', weekly: [5], futureVisible: false, maxMB: 5,
    holidays: [
      { date: '2026-10-29', bn: 'দুর্গাপূজা (সরকারি ছুটি)' },
      { date: '2026-11-04', bn: 'শিক্ষা সফর' },
      { date: '2026-12-16', bn: 'মহান বিজয় দিবস' },
      { date: '2026-12-25', bn: 'বড়দিন' },
    ],
  };
  const isOff = iso => settings.weekly.includes(P(iso).dow) || settings.holidays.some(h => h.date === iso);
  const nextSchoolDay = iso => { let d = add(iso, 1); while (isOff(d)) d = add(d, 1); return d; };

  const teachers = [
    ['t1', 'মাওলানা আব্দুর রহিম', 'Maulana Abdur Rahim', 'সহকারী শিক্ষক (গণিত)', '01711000001', 'rahim@arrahman.edu.bd', '2019-01-06', 'active'],
    ['t2', 'হাফেজ মাওলানা ইউসুফ আলী', 'Hafez Maulana Yusuf Ali', 'সিনিয়র শিক্ষক (কুরআন)', '01711000002', 'yusuf@arrahman.edu.bd', '2016-07-01', 'active'],
    ['t3', 'মুফতি হাবিবুর রহমান', 'Mufti Habibur Rahman', 'সহকারী শিক্ষক (আরবি)', '01711000003', 'habib@arrahman.edu.bd', '2018-03-11', 'active'],
    ['t4', 'মোসাঃ নাসরিন আক্তার', 'Mst. Nasrin Akter', 'সহকারী শিক্ষিকা (বাংলা)', '01711000004', 'nasrin@arrahman.edu.bd', '2020-01-02', 'active'],
    ['t5', 'মোঃ শফিকুল ইসলাম', 'Md. Shafiqul Islam', 'সহকারী শিক্ষক (ইংরেজি)', '01711000005', 'shafiq@arrahman.edu.bd', '2021-02-14', 'active'],
    ['t6', 'মোসাঃ ফারহানা ইয়াসমিন', 'Mst. Farhana Yasmin', 'সহকারী শিক্ষিকা (বিজ্ঞান)', '01711000006', 'farhana@arrahman.edu.bd', '2022-01-09', 'active'],
    ['t7', 'মাওলানা কামরুল হাসান', 'Maulana Kamrul Hasan', 'সহকারী শিক্ষক (ইসলাম শিক্ষা)', '01711000007', 'kamrul@arrahman.edu.bd', '2017-08-20', 'active'],
    ['t8', 'মোঃ তানভীর আহমেদ', 'Md. Tanvir Ahmed', 'প্রশিক্ষক (অ্যাবাকাস)', '01711000008', 'tanvir@arrahman.edu.bd', '2024-01-07', 'active'],
    ['t9', 'মোসাঃ রুমানা পারভীন', 'Mst. Rumana Parvin', 'সহকারী শিক্ষিকা (গণিত)', '01711000009', 'rumana@arrahman.edu.bd', '2023-01-08', 'leave'],
  ].map(r => ({ id: r[0], name: r[1], nameEn: r[2], desig: r[3], phone: r[4], email: r[5], join: r[6], status: r[7] }));
  const teacher = id => teachers.find(t => t.id === id);

  const subjects = [
    { id: 'quran', bn: 'কুরআন মাজিদ' }, { id: 'arabic', bn: 'আরবি' }, { id: 'bangla', bn: 'বাংলা' }, { id: 'english', bn: 'ইংরেজি' },
    { id: 'math', bn: 'গণিত' }, { id: 'science', bn: 'বিজ্ঞান' }, { id: 'islam', bn: 'ইসলাম শিক্ষা' }, { id: 'abacus', bn: 'অ্যাবাকাস' },
  ];
  const GENERAL = { id: 'general', bn: 'সাধারণ নোট' };
  const subj = id => id === 'general' ? GENERAL : subjects.find(s => s.id === id);
  const classes = [['c1', '১ম শ্রেণি'], ['c2', '২য় শ্রেণি'], ['c3', '৩য় শ্রেণি'], ['c4', '৪র্থ শ্রেণি'], ['c5', '৫ম শ্রেণি'], ['c6', '৬ষ্ঠ শ্রেণি'], ['c7', '৭ম শ্রেণি'], ['c8', '৮ম শ্রেণি']].map(([id, n]) => ({ id, bn: n }));
  const cls = id => classes.find(c => c.id === id);
  const SEC = { A: 'ক', B: 'খ' };
  const csLabel = (c, s) => `${cls(c).bn} – ${SEC[s]} শাখা`;

  // assignment matrix: key `${cls}-${sec}-${subj}` -> teacherId
  const defaultT = { quran: 't2', arabic: 't3', bangla: 't4', english: 't5', math: 't1', science: 't6', islam: 't7', abacus: 't8' };
  const assign = {}; const classTeacher = {};
  const ctRot = ['t4', 't7', 't2', 't3', 't1', 't5', 't6', 't3'];
  classes.forEach((c, ci) => ['A', 'B'].forEach((s, si) => {
    subjects.forEach(sb => { assign[`${c.id}-${s}-${sb.id}`] = defaultT[sb.id]; });
    if (ci < 4 && si === 1) assign[`${c.id}-${s}-math`] = 't9';
    classTeacher[`${c.id}-${s}`] = si === 0 ? ctRot[ci] : ctRot[(ci + 3) % 8];
  }));
  classTeacher['c5-A'] = 't1'; classTeacher['c5-B'] = 't5';
  assign['c6-B-math'] = null; assign['c8-B-science'] = null;
  ['c1', 'c2'].forEach(c => ['A', 'B'].forEach(s => { delete assign[`${c}-${s}-science`]; }));
  classTeacher['c7-B'] = 't1';

  const me = 't1';
  const myAssignments = [
    { cls: 'c5', sec: 'A', subj: 'math', ct: true },
    { cls: 'c5', sec: 'B', subj: 'math', ct: false },
    { cls: 'c6', sec: 'A', subj: 'math', ct: false },
    { cls: 'c4', sec: 'A', subj: 'math', ct: false },
  ];

  const roster = [
    ['01', 'আব্দুর রহমান'], ['02', 'আয়েশা সিদ্দিকা'], ['03', 'মুহাম্মদ ইব্রাহিম'], ['04', 'ফাতেমা তুজ জোহরা'], ['05', 'তাহমিদ হাসান'],
    ['06', 'নুসাইবা ইসলাম'], ['07', 'ইয়াসিন আরাফাত'], ['08', 'সুমাইয়া আক্তার'], ['09', 'রাফসান জানি'], ['10', 'মারিয়াম বিনতে হাসান'],
    ['11', 'আহনাফ তাহমিদ'], ['12', 'মোহাম্মদ আব্দুল্লাহ আল মামুন'], ['13', 'হাফসা খাতুন'], ['14', 'উমর ফারুক'], ['15', 'জান্নাতুল ফেরদৌস'],
    ['16', 'সাকিব আল হাসান'], ['17', 'তাসনিম জাহান'], ['18', 'আবু বকর সিদ্দিক'],
  ].map(([roll, name]) => ({ roll, name, id: '202401' + roll }));
  const classSize = 35;

  const phrases = ['পৃষ্ঠা __ থেকে __ পড়ে আসবে', 'মুখস্থ করবে', 'খাতায় লিখে আনবে', 'অনুশীলনী __ সমাধান করবে', 'সূরা __ মুখস্থ করবে'];
  const remarkTypes = [
    { id: 'praise', bn: 'প্রশংসা', icon: '★', fg: 'var(--success)', bg: 'var(--success-bg)' },
    { id: 'nohw', bn: 'বাড়ির কাজ আনেনি', icon: '!', fg: 'var(--warn)', bg: 'var(--warn-bg)' },
    { id: 'inattentive', bn: 'অমনোযোগী', icon: '!', fg: 'var(--warn)', bg: 'var(--warn-bg)' },
    { id: 'absent', bn: 'অনুপস্থিত', icon: '○', fg: 'var(--danger)', bg: 'var(--danger-bg)' },
    { id: 'other', bn: 'অন্যান্য', icon: '✎', fg: 'var(--info)', bg: 'var(--info-bg)' },
  ];

  const E = (id, d, c, s, sb, t, status, tm, x, seen) => ({ id, date: d, cls: c, sec: s, subj: sb, teacher: t, status, time: tm,
    today: x[0] || '', next: x[1] || '', hw: x[2] || '', due: x[2] ? (x[3] || nextSchoolDay(d)) : '', note: x[4] || '', attach: x[5] || [], seen: seen ?? 0, total: classSize });
  const entries = [
    E('e1', today, 'c5', 'A', 'quran', 't2', 'published', '09:20', ['সূরা আল-মুলক আয়াত ১–১০ তিলাওয়াত ও তাজবিদ', 'আয়াত ১১–২০ দেখে পড়ে আসবে', 'আয়াত ১–৫ মুখস্থ করবে'], 31),
    E('e2', today, 'c5', 'A', 'arabic', 't3', 'published', '10:05', ['দারস ৭: الفعل الماضي (অতীত কাল)', 'দারস ৮ পড়ে আসবে', 'অনুশীলনী ৭ এর ক ও খ খাতায় লিখে আনবে'], 29),
    E('e3', today, 'c5', 'A', 'bangla', 't4', 'published', '11:10', ['কবিতা: আমাদের ছোট নদী — আবৃত্তি ও শব্দার্থ', 'পৃষ্ঠা ৪২ থেকে ৪৬ পড়ে আসবে', 'কবিতার প্রথম ৮ লাইন মুখস্থ করবে', '2026-10-20', '', [{ type: 'img', name: 'বাংলা-পৃষ্ঠা-৪২.jpg' }]], 28),
    E('e4', today, 'c5', 'A', 'math', 't1', 'published', '12:15', ['ভগ্নাংশের যোগ ও বিয়োগ (অনুশীলনী ৪.২)', 'অনুশীলনী ৪.৩ এর ১–৫ দেখে আসবে', 'অনুশীলনী ৪.২ এর ৬–১০ সমাধান করবে', '', 'কাল জ্যামিতি বক্স আনবে', [{ type: 'pdf', name: 'ভগ্নাংশ-ওয়ার্কশিট.pdf' }]], 28),
    E('e5', today, 'c5', 'A', 'english', 't5', 'draft', '12:50', ['Unit 5: My Family — reading'], 0),
    E('e6', today, 'c5', 'A', 'islam', 't7', 'published', '01:40', ['অজুর ফরজ ও সুন্নত', 'পাঠ ৫ পড়ে আসবে', '', '', 'বৃহস্পতিবার জায়নামাজ আনবে'], 22),
    E('e7', today, 'c5', 'A', 'general', 't1', 'published', '02:10', ['', '', '', '', 'আগামী রবিবার (২৫ অক্টোবর) থেকে মাসিক পরীক্ষা শুরু। পরীক্ষার ফি ২২ অক্টোবরের মধ্যে জমা দিতে হবে।'], 30),
    E('e8', today, 'c5', 'B', 'math', 't1', 'draft', '12:58', ['ভগ্নাংশের যোগ (অনুশীলনী ৪.১)', 'অনুশীলনী ৪.২ দেখে আসবে'], 0),
    E('e9', '2026-10-15', 'c5', 'A', 'math', 't1', 'published', '12:20', ['ভগ্নাংশের ধারণা ও সমতুল ভগ্নাংশ', 'অনুশীলনী ৪.২ দেখে আসবে', 'অনুশীলনী ৪.১ এর ১–৮ সমাধান করবে', '2026-10-17'], 33),
    E('e10', '2026-10-15', 'c5', 'A', 'arabic', 't3', 'published', '10:00', ['দারস ৬: শব্দ গঠন', 'দারস ৭ পড়ে আসবে', 'অনুশীলনী ৬ খাতায় লিখে আনবে', '2026-10-17'], 34),
    E('e11', '2026-10-15', 'c5', 'A', 'quran', 't2', 'published', '09:15', ['সূরা আল-কালাম আয়াত ৪০–৫২', 'সূরা আল-মুলক শুরু', 'সূরা আল-কালাম শেষ ১০ আয়াত মুখস্থ করবে', '2026-10-17'], 35),
    E('e12', '2026-10-14', 'c5', 'A', 'math', 't1', 'published', '12:10', ['গ.সা.গু ও ল.সা.গু পুনরালোচনা', 'ভগ্নাংশ অধ্যায় পড়ে আসবে', 'অনুশীলনী ৩.৪ এর ৫–১০ সমাধান করবে'], 32),
    E('e13', '2026-10-14', 'c5', 'A', 'science', 't6', 'published', '11:30', ['উদ্ভিদের খাদ্য তৈরি (সালোকসংশ্লেষণ)', 'পৃষ্ঠা ৩৫ থেকে ৩৮ পড়ে আসবে', 'একটি পাতার ছবি এঁকে আনবে', '2026-10-21'], 30),
    E('e14', '2026-10-13', 'c5', 'A', 'bangla', 't4', 'published', '11:05', ['ব্যাকরণ: বিশেষ্য পদ', 'বিশেষণ পদ পড়ে আসবে', '১০টি বিশেষ্য পদ খাতায় লিখে আনবে'], 33),
    E('e15', '2026-10-12', 'c5', 'A', 'english', 't5', 'published', '12:45', ['Unit 4: Our School — new words', 'Unit 5 reading', 'Write 5 sentences about your school'], 31),
    E('e16', '2026-10-11', 'c5', 'A', 'abacus', 't8', 'published', '02:30', ['দুই অঙ্কের যোগ — বিডস অনুশীলন', 'তিন অঙ্কের যোগ', 'ওয়ার্কশিট ১২ সমাধান করবে', '2026-10-19', '', [{ type: 'pdf', name: 'অ্যাবাকাস-ওয়ার্কশিট-১২.pdf' }]], 35),
    E('e17', today, 'c6', 'A', 'math', 't1', 'published', '10:40', ['সরল সমীকরণ', 'অনুশীলনী ৬.২ দেখে আসবে', 'অনুশীলনী ৬.১ এর ১–৬ সমাধান করবে'], 19),
    E('e18', '2026-10-15', 'c4', 'A', 'math', 't1', 'published', '11:50', ['গুণ: তিন অঙ্কের সংখ্যা', 'ভাগ অধ্যায় শুরু', 'অনুশীলনী ৩ এর ১–৫ সমাধান করবে', '2026-10-17'], 30),
    E('e19', '2026-10-14', 'c5', 'B', 'math', 't1', 'published', '12:30', ['গ.সা.গু ও ল.সা.গু পুনরালোচনা', 'ভগ্নাংশ অধ্যায়', 'অনুশীলনী ৩.৪ এর ১–৫'], 27),
  ];

  const remarks = [
    { id: 'r1', date: today, stuId: '20240112', type: 'praise', subj: 'math', teacher: 't1', text: 'আজ বোর্ডে সব অঙ্ক সঠিক করেছে। মাশাআল্লাহ!' },
    { id: 'r2', date: '2026-10-15', stuId: '20240112', type: 'nohw', subj: 'arabic', teacher: 't3', text: 'অনুশীলনী ৬ এর বাড়ির কাজ আনেনি।' },
    { id: 'r3', date: today, stuId: '20240107', type: 'nohw', subj: 'math', teacher: 't1', text: '' },
    { id: 'r4', date: today, stuId: '20240103', type: 'absent', subj: 'math', teacher: 't1', text: '' },
  ];

  const notSeen = ['আব্দুর রহমান', 'তাহমিদ হাসান', 'রাফসান জানি', 'হাফসা খাতুন', 'সাকিব আল হাসান', 'আবু বকর সিদ্দিক', 'তাসনিম জাহান'].map(n => roster.find(r => r.name === n));

  const initials = name => name.replace(/^(মোহাম্মদ|মুহাম্মদ|মোঃ|মোসাঃ|মাওলানা|হাফেজ|মুফতি)\s+/g, '').trim().slice(0, 1);

  window.DY = { bn, date, dateShort, dayName, dayShort, time, add, P, today, settings, isOff, nextSchoolDay, teachers, teacher, subjects, GENERAL, subj, classes, cls, SEC, csLabel,
    assign, classTeacher, defaultT, me, myAssignments, roster, classSize, phrases, remarkTypes, entries, remarks, notSeen, initials };
})();
