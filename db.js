import sqlite3 from 'sqlite3';
import { open } from 'sqlite';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const dataDir = path.join(__dirname, 'data');
const dbFile = path.join(dataDir, 'database.sqlite');

await fs.promises.mkdir(dataDir, { recursive: true });

export const db = await open({
  filename: dbFile,
  driver: sqlite3.Database
});

await db.exec(`CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  email TEXT UNIQUE,
  username TEXT UNIQUE,
  password TEXT
)`);

await db.exec(`CREATE TABLE IF NOT EXISTS notes (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  username TEXT,
  note_type TEXT,
  title TEXT,
  content TEXT,
  due_date TEXT,
  priority TEXT,
  is_completed INTEGER DEFAULT 0,
  color TEXT,
  created_at DATETIME DEFAULT (datetime('now','localtime'))
)`);
