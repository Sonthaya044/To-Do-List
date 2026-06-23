import express from 'express';
import session from 'express-session';
import SQLiteStoreFactory from 'connect-sqlite3';
import bcrypt from 'bcrypt';
import path from 'path';
import { fileURLToPath } from 'url';
import { db } from '../db.js';
import dotenv from 'dotenv';

dotenv.config();

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const SQLiteStore = SQLiteStoreFactory(session);

const app = express();

app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, '../views'));
app.use(express.static(path.join(__dirname, '../public')));
app.use(express.urlencoded({ extended: true }));
app.use(express.json());
app.use(session({
  store: new SQLiteStore({ db: 'sessions.sqlite', dir: path.join(__dirname, '../data') }),
  secret: process.env.SESSION_SECRET || 'change_this_secret',
  resave: false,
  saveUninitialized: false,
  cookie: { maxAge: 24 * 60 * 60 * 1000 }
}));

app.use((req, res, next) => {
  res.locals.user = req.session.user;
  next();
});

app.get('/', (req, res) => {
  if (req.session.user) return res.redirect('/home');
  return res.redirect('/landing');
});
app.get('/landing', (req, res) => res.render('landing'));
app.get('/auth', (req, res) => {
  if (req.session.user) return res.redirect('/home');
  return res.render('auth', { message: null, activeForm: 'login' });
});

app.post('/auth/register', async (req, res) => {
  const { email, username, password, confirm_password } = req.body;
  if (!email || !username || !password || !confirm_password) {
    return res.render('auth', { message: '❌ กรุณากรอกข้อมูลให้ครบถ้วน', activeForm: 'register' });
  }
  if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) {
    return res.render('auth', { message: '❌ อีเมลไม่ถูกต้อง', activeForm: 'register' });
  }
  if (username.length < 4) {
    return res.render('auth', { message: '❌ ชื่อผู้ใช้ต้องมีอย่างน้อย 4 ตัวอักษร', activeForm: 'register' });
  }
  if (password.length < 8) {
    return res.render('auth', { message: '❌ รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร', activeForm: 'register' });
  }
  if (password !== confirm_password) {
    return res.render('auth', { message: '❌ รหัสผ่านไม่ตรงกัน', activeForm: 'register' });
  }

  const existing = await db.get('SELECT id FROM users WHERE email = ? OR username = ?', [email, username]);
  if (existing) {
    return res.render('auth', { message: '❌ อีเมลหรือชื่อผู้ใช้นี้ถูกใช้แล้ว', activeForm: 'register' });
  }

  const hashed = await bcrypt.hash(password, 10);
  await db.run('INSERT INTO users (email, username, password) VALUES (?, ?, ?)', [email, username, hashed]);
  return res.render('auth', { message: '✅ สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ', activeForm: 'login' });
});

app.post('/auth/login', async (req, res) => {
  const { username, password } = req.body;
  const user = await db.get('SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1', [username, username]);
  if (!user || !(await bcrypt.compare(password, user.password))) {
    return res.render('auth', { message: '❌ ชื่อผู้ใช้/รหัสผ่านไม่ถูกต้อง', activeForm: 'login' });
  }
  req.session.user = { id: user.id, username: user.username };
  return res.redirect('/home');
});

app.get('/logout', (req, res) => {
  req.session.destroy(() => res.redirect('/auth'));
});

app.use((req, res, next) => {
  if (req.path.startsWith('/public')) return next();
  if (!req.session.user && req.path !== '/auth' && req.path !== '/auth/register' && req.path !== '/auth/login' && req.path !== '/landing') {
    return res.redirect('/auth');
  }
  next();
});

function countPriorities(notes) {
  return notes.reduce((counters, note) => {
    if (!note.is_completed) {
      if (note.priority === 'High') counters.high++;
      if (note.priority === 'Medium') counters.medium++;
      if (note.priority === 'Low') counters.low++;
    }
    return counters;
  }, { high: 0, medium: 0, low: 0 });
}

app.get('/home', async (req, res) => {
  const notes = await db.all('SELECT * FROM notes WHERE username = ? ORDER BY id DESC', [req.session.user.username]);
  const counter = countPriorities(notes);
  res.render('home', { notes, counter, username: req.session.user.username });
});

app.post('/save', async (req, res) => {
  const { title, content, due_date, priority, color } = req.body;
  await db.run('INSERT INTO notes (username, title, content, due_date, priority, color) VALUES (?, ?, ?, ?, ?, ?)', [req.session.user.username, title, content, due_date || null, priority || 'Medium', color || '#fff7b0']);
  res.redirect('/home');
});

app.post('/update', async (req, res) => {
  const { note_id, title, content, due_date, priority, color } = req.body;
  await db.run('UPDATE notes SET title=?, content=?, color=?, due_date=?, priority=? WHERE id=? AND username=?', [title, content, color || '#fff7b0', due_date || null, priority || 'Medium', note_id, req.session.user.username]);
  res.redirect('/home');
});

app.get('/delete', async (req, res) => {
  await db.run('DELETE FROM notes WHERE id = ? AND username = ?', [req.query.id, req.session.user.username]);
  res.redirect('/home');
});

app.post('/delete_completed', async (req, res) => {
  await db.run('DELETE FROM notes WHERE username = ? AND is_completed = 1', [req.session.user.username]);
  res.redirect('/today');
});

app.post('/toggle_status', async (req, res) => {
  const { note_id, status } = req.body;
  await db.run('UPDATE notes SET is_completed = ? WHERE id = ? AND username = ?', [status ? 1 : 0, note_id, req.session.user.username]);
  res.json({ success: true });
});

app.get('/today', async (req, res) => {
  const today = new Date().toISOString().slice(0, 10);
  const today_notes = await db.all('SELECT * FROM notes WHERE username = ? AND due_date = ? ORDER BY is_completed ASC, priority DESC, id DESC', [req.session.user.username, today]);
  const all_notes = await db.all('SELECT priority, is_completed FROM notes WHERE username = ?', [req.session.user.username]);
  const counter = countPriorities(all_notes);
  const completed_count = today_notes.filter(n => n.is_completed).length;
  res.render('today', { today_notes, counter, completed_count, todayString: today });
});

app.get('/upcoming', async (req, res) => {
  const today = new Date();
  const start = today.toISOString().slice(0, 10);
  const end = new Date(today.getTime() + 3 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10);
  const upcoming_notes = await db.all('SELECT * FROM notes WHERE username = ? AND due_date > ? AND due_date <= ? AND is_completed = 0 ORDER BY due_date ASC, priority DESC, id DESC', [req.session.user.username, start, end]);
  const grouped_notes = {};
  upcoming_notes.forEach(note => { groupByDate(grouped_notes, note); });
  const all_notes = await db.all('SELECT priority, is_completed FROM notes WHERE username = ?', [req.session.user.username]);
  const counter = countPriorities(all_notes);
  res.render('upcoming', { grouped_notes, counter });
});

function groupByDate(store, note) {
  const day = note.due_date;
  if (!store[day]) store[day] = [];
  store[day].push(note);
}

app.get('/calendar', async (req, res) => {
  const month = parseInt(req.query.month, 10) || new Date().getMonth() + 1;
  const year = parseInt(req.query.year, 10) || new Date().getFullYear();
  const firstDay = new Date(year, month - 1, 1);
  const daysInMonth = new Date(year, month, 0).getDate();
  const offset = firstDay.getDay();
  const monthName = firstDay.toLocaleString('default', { month: 'long' });
  const prev = getMonthYear(month, year, -1);
  const next = getMonthYear(month, year, 1);
  const events = await db.all('SELECT title, due_date, priority, is_completed FROM notes WHERE username = ? AND strftime("%m", due_date) = ? AND strftime("%Y", due_date) = ?', [req.session.user.username, String(month).padStart(2, '0'), String(year)]);
  const eventMap = {};
  events.forEach(note => { const day = String(new Date(note.due_date).getDate()); if (!eventMap[day]) eventMap[day] = []; eventMap[day].push(note); });
  const all_notes = await db.all('SELECT priority, is_completed FROM notes WHERE username = ?', [req.session.user.username]);
  const counter = countPriorities(all_notes);
  res.render('calendar', { month, year, monthName, daysInMonth, offset, prev, next, eventMap, counter });
});

function getMonthYear(month, year, delta) {
  const date = new Date(year, month - 1 + delta, 1);
  return { month: date.getMonth() + 1, year: date.getFullYear() };
}

app.get('*', (req, res) => res.redirect('/home'));

export default app;
