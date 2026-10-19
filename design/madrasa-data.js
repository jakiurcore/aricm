(function () {
  const BN = '০১২৩৪৫৬৭৮৯';
  const num = (v, lang) => lang === 'en' ? String(v) : String(v).replace(/[0-9]/g, d => BN[d]);
  const MBN = ['জানুয়ারি','ফেব্রুয়ারি','মার্চ','এপ্রিল','মে','জুন','জুলাই','আগস্ট','সেপ্টেম্বর','অক্টোবর','নভেম্বর','ডিসেম্বর'];
  const MEN = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  const DBN = ['রবিবার','সোমবার','মঙ্গলবার','বুধবার','বৃহস্পতিবার','শুক্রবার','শনিবার'];
  const DEN = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
  const parts = iso => { const [y, m, d] = iso.split('-').map(Number); return { y, m, d, dow: new Date(Date.UTC(y, m - 1, d)).getUTCDay() }; };
  const date = (iso, lang) => { const p = parts(iso); return lang === 'en' ? `${p.d} ${MEN[p.m - 1]} ${p.y}` : `${num(p.d)} ${MBN[p.m - 1]} ${num(p.y)}`; };
  const dayM = (iso, lang) => { const p = parts(iso); return { d: num(p.d, lang), m: lang === 'en' ? MEN[p.m - 1] : MBN[p.m - 1] }; };
  const day = (iso, lang) => (lang === 'en' ? DEN : DBN)[parts(iso).dow];
  const t = (o, lang) => (o && typeof o === 'object' && ('bn' in o || 'en' in o)) ? (o[lang] ?? o.bn) : o;

  const inst = {
    name: { bn: 'আর রহমান ইন্টারন্যাশনাল ক্যাডেট মাদরাসা', en: 'AR Rahman International Cadet Madrasa' },
    short: { bn: 'আর রহমান ক্যাডেট মাদরাসা', en: 'AR Rahman Cadet Madrasa' },
    arabic: 'الرحمن انترناشونال كاديت مدرسة',
    est: { bn: 'স্থাপিত: ২০২৪ ইং', en: 'Established: 2024' },
    address: { bn: 'লেংগা বাজার, কলেজ পাড়া, গাইবান্ধা সদর, গাইবান্ধা', en: 'Lengga Bazar, College Para, Gaibandha Sadar, Gaibandha' },
    phones: ['01714844346', '01761781338'],
    email: 'info@arrahmanmadrasa.edu.bd',
    taglines: { bn: ['শিশুর মেধা বিকাশে আমাদের প্রতিশ্রুতি…', 'ছোট থেকেই মেধার ভিত্তি গড়ি', 'আপনারা সবাই সাদর আমন্ত্রিত!'], en: ['Our commitment to nurturing every child’s talent…', 'Building the foundation of talent from an early age', 'You are all warmly invited!'] },
    map: 'https://maps.google.com/maps?q=Gaibandha%20Sadar%20College%20Para&z=14&output=embed',
  };

  const nav = [
    { id: 'home', bn: 'হোম', en: 'Home' }, { id: 'about', bn: 'আমাদের সম্পর্কে', en: 'About' },
    { id: 'academics', bn: 'শিক্ষা কার্যক্রম', en: 'Academics' }, { id: 'admission', bn: 'ভর্তি', en: 'Admission' },
    { id: 'notices', bn: 'নোটিশ', en: 'Notices' }, { id: 'routine', bn: 'পরীক্ষার রুটিন', en: 'Exam Routine' },
    { id: 'results', bn: 'ফলাফল', en: 'Results' }, { id: 'gallery', bn: 'গ্যালারি', en: 'Gallery' },
    { id: 'contact', bn: 'যোগাযোগ', en: 'Contact' },
  ];

  const noticeCats = [
    { id: 'all', bn: 'সব', en: 'All' }, { id: 'admission', bn: 'ভর্তি', en: 'Admission' }, { id: 'exam', bn: 'পরীক্ষা', en: 'Exam' },
    { id: 'result', bn: 'ফলাফল', en: 'Result' }, { id: 'holiday', bn: 'ছুটি', en: 'Holiday' }, { id: 'general', bn: 'সাধারণ', en: 'General' },
  ];
  const notices = [
    { id: 'n1', cat: 'admission', date: '2026-10-18', pinned: true, pdf: true, ticker: true, title: { bn: '২০২৬–২৭ শিক্ষাবর্ষে ভর্তি চলছে — ভর্তি শুরু ২১ অক্টোবর', en: 'Admission open for 2026–27 — starts 21 October' } },
    { id: 'n2', cat: 'general', date: '2026-10-16', pinned: true, pdf: false, ticker: true, title: { bn: 'নভেম্বরের ১ তারিখে সকল শ্রেণিতে ফ্রি ক্লাস', en: 'Free classes for all grades on 1 November' } },
    { id: 'n3', cat: 'exam', date: '2026-10-12', pinned: false, pdf: true, ticker: true, title: { bn: 'বার্ষিক পরীক্ষা ২০২৬-এর রুটিন প্রকাশ', en: 'Annual Exam 2026 routine published' } },
    { id: 'n4', cat: 'result', date: '2026-10-05', pinned: false, pdf: true, ticker: false, title: { bn: 'অর্ধবার্ষিক পরীক্ষা ২০২৬-এর ফলাফল প্রকাশিত', en: 'Half-Yearly Exam 2026 results published' } },
    { id: 'n5', cat: 'exam', date: '2026-09-28', pinned: false, pdf: false, ticker: false, title: { bn: 'পরীক্ষার ফি জমাদানের শেষ তারিখ ২০ নভেম্বর', en: 'Exam fee deadline: 20 November' } },
    { id: 'n6', cat: 'holiday', date: '2026-09-20', pinned: false, pdf: true, ticker: false, title: { bn: 'ঈদে মিলাদুন্নবী উপলক্ষে ছুটির নোটিশ', en: 'Holiday notice for Eid-e-Miladunnabi' } },
    { id: 'n7', cat: 'general', date: '2026-09-10', pinned: false, pdf: false, ticker: false, title: { bn: 'অভিভাবক সমাবেশ — শনিবার সকাল ১০টা', en: 'Guardians’ meeting — Saturday 10 AM' } },
    { id: 'n8', cat: 'general', date: '2026-08-30', pinned: false, pdf: false, ticker: false, title: { bn: 'অ্যাবাকাস প্রোগ্রামের নতুন ব্যাচ শুরু', en: 'New Abacus batch begins' } },
  ];
  const noticeBody = {
    bn: 'সম্মানিত অভিভাবকবৃন্দের অবগতির জন্য জানানো যাচ্ছে যে, উল্লিখিত বিষয়ে বিস্তারিত তথ্য সংযুক্ত PDF ফাইলে দেওয়া হলো। যেকোনো প্রয়োজনে অফিস চলাকালীন সময়ে (সকাল ৮টা – বিকাল ৪টা) মাদরাসা অফিসে যোগাযোগ করুন অথবা ফোন করুন।',
    en: 'Respected guardians are informed that full details of the above are provided in the attached PDF. For any query, please contact the madrasa office during office hours (8 AM – 4 PM) or call us.',
  };

  const classes = [
    { id: 'play', bn: 'প্লে', en: 'Play' }, { id: 'nursery', bn: 'নার্সারি', en: 'Nursery' },
    { id: 'c1', bn: '১ম শ্রেণি', en: 'Class 1' }, { id: 'c2', bn: '২য় শ্রেণি', en: 'Class 2' }, { id: 'c3', bn: '৩য় শ্রেণি', en: 'Class 3' },
    { id: 'c4', bn: '৪র্থ শ্রেণি', en: 'Class 4' }, { id: 'c5', bn: '৫ম শ্রেণি', en: 'Class 5' }, { id: 'c6', bn: '৬ষ্ঠ শ্রেণি', en: 'Class 6' },
    { id: 'c7', bn: '৭ম শ্রেণি', en: 'Class 7' }, { id: 'c8', bn: '৮ম শ্রেণি', en: 'Class 8' }, { id: 'hifz', bn: 'হিফজ বিভাগ', en: 'Hifz' },
  ];
  const sections = [{ id: 'A', bn: 'ক', en: 'A' }, { id: 'B', bn: 'খ', en: 'B' }];

  const subjects = [
    { id: 'quran', bn: 'কুরআন মাজিদ', en: 'Quran Majid', comps: ['written', 'oral'] },
    { id: 'arabic', bn: 'আরবি', en: 'Arabic', comps: ['written', 'mcq'] },
    { id: 'bangla', bn: 'বাংলা', en: 'Bangla', comps: ['written', 'mcq'] },
    { id: 'english', bn: 'ইংরেজি', en: 'English', comps: ['written', 'mcq'] },
    { id: 'math', bn: 'গণিত', en: 'Mathematics', comps: ['written', 'mcq'] },
    { id: 'science', bn: 'বিজ্ঞান', en: 'Science', comps: ['written', 'practical'] },
    { id: 'islam', bn: 'ইসলাম শিক্ষা', en: 'Islamic Studies', comps: ['written', 'mcq'] },
    { id: 'abacus', bn: 'অ্যাবাকাস', en: 'Abacus', comps: ['written', 'oral'] },
  ];
  const comps = { written: { bn: 'লিখিত', en: 'Written', full: 70 }, mcq: { bn: 'MCQ', en: 'MCQ', full: 30 }, oral: { bn: 'মৌখিক', en: 'Oral', full: 30 }, practical: { bn: 'ব্যবহারিক', en: 'Practical', full: 30 } };

  const exams = [
    { id: 'annual26', bn: 'বার্ষিক পরীক্ষা ২০২৬', en: 'Annual Exam 2026', session: '2026', from: '2026-12-01', to: '2026-12-10', status: 'upcoming', published: false, admitReleased: true },
    { id: 'model26', bn: 'মডেল টেস্ট ২০২৬', en: 'Model Test 2026', session: '2026', from: '2026-09-14', to: '2026-09-20', status: 'completed', published: true },
    { id: 'half26', bn: 'অর্ধবার্ষিক পরীক্ষা ২০২৬', en: 'Half-Yearly Exam 2026', session: '2026', from: '2026-06-15', to: '2026-06-24', status: 'completed', published: true },
    { id: 'monthly26', bn: 'মাসিক পরীক্ষা — অক্টোবর', en: 'Monthly Test — October', session: '2026', from: '2026-10-25', to: '2026-10-27', status: 'draft', published: false },
  ];
  const routineFor = (examId) => {
    const ex = exams.find(e => e.id === examId) || exams[0];
    const start = new Date(ex.from + 'T00:00:00Z'); const out = []; let i = 0;
    for (const s of subjects) {
      while ([5].includes(start.getUTCDay())) start.setUTCDate(start.getUTCDate() + 1);
      out.push({ date: start.toISOString().slice(0, 10), subj: s, time: i % 2 ? '10:00 – 12:00' : '10:00 – 01:00', room: i % 3 === 0 ? '101' : '102' });
      start.setUTCDate(start.getUTCDate() + 1); i++;
    }
    return out;
  };

  const grading = [
    { min: 80, max: 100, grade: 'A+', gp: 5.0 }, { min: 70, max: 79, grade: 'A', gp: 4.0 }, { min: 60, max: 69, grade: 'A-', gp: 3.5 },
    { min: 50, max: 59, grade: 'B', gp: 3.0 }, { min: 40, max: 49, grade: 'C', gp: 2.0 }, { min: 33, max: 39, grade: 'D', gp: 1.0 }, { min: 0, max: 32, grade: 'F', gp: 0.0 },
  ];
  const gradeOf = p => grading.find(g => p >= g.min && p <= g.max) || grading[grading.length - 1];

  const student = {
    id: '20240101', roll: '12', classId: 'c5', section: 'A', session: '2026', dob: '2015-03-14', blood: 'B+', gender: { bn: 'ছেলে', en: 'Male' },
    name: { bn: 'মোহাম্মদ আব্দুল্লাহ আল মামুন', en: 'Mohammad Abdullah Al Mamun' },
    father: { bn: 'মোঃ রফিকুল ইসলাম', en: 'Md. Rafiqul Islam' }, mother: { bn: 'মোসাঃ সালমা বেগম', en: 'Mst. Salma Begum' },
    guardianPhone: '01712345678', relation: { bn: 'পিতা', en: 'Father' },
    address: { bn: 'কলেজ পাড়া, গাইবান্ধা সদর, গাইবান্ধা', en: 'College Para, Gaibandha Sadar, Gaibandha' },
    admitted: '2024-01-05', division: { bn: 'কিডস বিভাগ', en: 'Kids Division' },
  };
  const markSeed = { half26: [86, 74, 81, 69, 92, 77, 88, 95], model26: [90, 78, 84, 72, 89, 80, 91, 97] };
  const marksheet = (examId) => {
    const seed = markSeed[examId] || markSeed.half26;
    const rows = subjects.map((s, i) => {
      const total = seed[i]; const [c1, c2] = s.comps; const f1 = comps[c1].full, f2 = 100 - f1;
      const a = Math.round(total * f1 / 100), b = total - a; const g = gradeOf(total);
      return { subj: s, parts: [{ k: c1, full: f1, got: a }, { k: c2, full: f2, got: b }], full: 100, pass: 33, got: total, grade: g.grade, gp: g.gp };
    });
    const total = rows.reduce((a, r) => a + r.got, 0); const gpa = rows.some(r => r.gp === 0) ? 0 : rows.reduce((a, r) => a + r.gp, 0) / rows.length;
    const final = gpa >= 5 ? 'A+' : gpa >= 4 ? 'A' : gpa >= 3.5 ? 'A-' : gpa >= 3 ? 'B' : gpa >= 2 ? 'C' : gpa >= 1 ? 'D' : 'F';
    return { rows, total, full: rows.length * 100, gpa: gpa.toFixed(2), final, position: examId === 'model26' ? 2 : 3, passed: gpa > 0, classSize: 38 };
  };

  const fees = [
    { examId: 'annual26', amount: 800, status: 'due', due: '2026-11-20' },
    { examId: 'model26', amount: 500, status: 'paid', paidOn: '2026-09-05', method: 'bKash', txn: 'BK7X92LQ4M', receipt: 'R-2026-0412' },
    { examId: 'half26', amount: 700, status: 'paid', paidOn: '2026-06-02', method: { bn: 'নগদ ক্যাশ', en: 'Cash' }, txn: '—', receipt: 'R-2026-0188' },
  ];

  const albums = [
    { id: 'a1', bn: 'বার্ষিক ক্রীড়া প্রতিযোগিতা ২০২৬', en: 'Annual Sports Day 2026', count: 12, date: '2026-02-20' },
    { id: 'a2', bn: 'হিফজ সমাপনী অনুষ্ঠান', en: 'Hifz Completion Ceremony', count: 9, date: '2026-04-11' },
    { id: 'a3', bn: 'অ্যাবাকাস ক্লাস', en: 'Abacus Classes', count: 8, date: '2026-07-02' },
    { id: 'a4', bn: 'ক্যাডেট প্যারেড ও পিটি', en: 'Cadet Parade & PT', count: 10, date: '2026-08-15' },
    { id: 'a5', bn: 'বিজয় দিবস উদযাপন', en: 'Victory Day Celebration', count: 7, date: '2025-12-16' },
    { id: 'a6', bn: 'ক্যাম্পাস ও শ্রেণিকক্ষ', en: 'Campus & Classrooms', count: 11, date: '2025-11-01' },
  ];

  const students = [
    ['20240101', 'মোহাম্মদ আব্দুল্লাহ আল মামুন', 'Mohammad Abdullah Al Mamun', 'c5', 'A', '12', 'active', 'paid'],
    ['20240102', 'আয়েশা সিদ্দিকা', 'Ayesha Siddika', 'c5', 'A', '13', 'active', 'due'],
    ['20240103', 'মুহাম্মাদ ইব্রাহিম খলিল', 'Muhammad Ibrahim Khalil', 'c5', 'B', '14', 'active', 'paid'],
    ['20240104', 'ফাতেমা তুজ জোহরা', 'Fatema Tuj Zohra', 'c4', 'A', '03', 'active', 'paid'],
    ['20240105', 'আব্দুর রহমান সিয়াম', 'Abdur Rahman Siam', 'c6', 'A', '07', 'active', 'due'],
    ['20240106', 'সুমাইয়া আক্তার', 'Sumaiya Akter', 'c3', 'B', '09', 'active', 'paid'],
    ['20240107', 'তানভীর আহমেদ', 'Tanvir Ahmed', 'hifz', 'A', '02', 'active', 'paid'],
    ['20240108', 'নুসরাত জাহান', 'Nusrat Jahan', 'c2', 'A', '05', 'inactive', 'due'],
    ['20240109', 'হাসান মাহমুদ', 'Hasan Mahmud', 'c7', 'A', '01', 'active', 'paid'],
    ['20240110', 'মারিয়াম খাতুন', 'Mariam Khatun', 'c1', 'B', '11', 'active', 'paid'],
  ].map(r => ({ id: r[0], name: { bn: r[1], en: r[2] }, classId: r[3], section: r[4], roll: r[5], status: r[6], fee: r[7] }));


  const U = (id, name, handle) => ({ src: 'https://images.unsplash.com/photo-' + id + '?auto=format&fit=crop&w=1200&q=70', credit: 'Photo by ' + name + ' on Unsplash', href: 'https://unsplash.com/@' + handle });
  const photos = {
    hero: U('1637034120715-cdd8f1c9347d', 'Wanman uthmaniyyah', 'wanman'),
    principal: U('1761475048666-ec46f9a5fd55', 'أخٌ في الله', 'mhrezaa'),
    campus: U('1721416308449-3ffac3e71121', 'Omar Elsharawy', 'esh3rwy'),
    ledge: U('1712249237537-8c5a0420653b', 'Aldin Nasrun', 'alnasrun'),
    floorRead: U('1712249239085-2161afc54d60', 'Aldin Nasrun', 'alnasrun'),
    boys: U('1629273229664-11fabc0becc0', 'Andri Helmansyah', 'andri_helmansyah'),
    girl: U('1629273229214-d96be4552b9a', 'Andri Helmansyah', 'andri_helmansyah'),
    robe: U('1779687581931-ed735b771d9f', 'Madrosah Sunnah', '26_ms'),
    twoRead: U('1779687581973-f78c394f6443', 'Madrosah Sunnah', '26_ms'),
    abacus: U('1611957082126-061f655ef1fb', 'luis arias', 'luis_arias'),
    shelves: U('1498243691581-b145c3f54a5a', 'Priscilla Du Preez', 'priscilladupreez'),
    hijabRead: U('1638040895680-59de2786b189', 'Fotos', 'fotospk'),
    court: U('1640488065806-e3cf96865965', 'Haidan', 'hydngallery'),
    tower: U('1736718126907-bb2232556351', 'Tolla', 'tolla'),
    books: U('1521587760476-6c12a4b040da', 'Iñaki del Olmo', 'inakihxz'),
    beads: U('1539584855745-e282984ced68', 'أخٌ في الله', 'mhrezaa'),
  };
  const photoPool = ['ledge', 'twoRead', 'boys', 'abacus', 'girl', 'robe', 'hijabRead', 'floorRead', 'court', 'shelves', 'books', 'beads', 'tower'].map(k => photos[k]);
  const albumCover = { a1: 'court', a2: 'robe', a3: 'abacus', a4: 'ledge', a5: 'tower', a6: 'campus' };
  const cls = id => classes.find(c => c.id === id) || classes[0];
  const sec = id => sections.find(s => s.id === id) || sections[0];

  window.MD = { photos, photoPool, albumCover, num, date, dayM, day, t, inst, nav, notices, noticeCats, noticeBody, classes, sections, subjects, comps, exams, routineFor, grading, gradeOf, student, marksheet, fees, albums, students, cls, sec,
    getLang: () => { try { return localStorage.getItem('arm_lang') || 'bn'; } catch (e) { return 'bn'; } },
    setLang: l => { try { localStorage.setItem('arm_lang', l); } catch (e) {} },
    getTheme: () => { try { return localStorage.getItem('arm_theme') || 'light'; } catch (e) { return 'light'; } },
    setTheme: v => { try { localStorage.setItem('arm_theme', v); } catch (e) {} },
  };
})();
