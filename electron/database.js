/**
 * Wangari Desktop — Local SQLite Database
 * Offline-first: all data lives locally, syncs to server when online.
 * Tables mirror the server MySQL schema for seamless bi-directional sync.
 */
'use strict';

const path = require('path');
const fs = require('fs');
const os = require('os');

const DB_DIR = path.join(os.homedir(), '.wangari');
const DB_PATH = path.join(DB_DIR, 'wangari_local.db');

let db = null;

function getDatabase() {
  if (db) return db;

  // Lazy-load better-sqlite3
  let Database;
  try {
    Database = require('better-sqlite3');
  } catch (e) {
    // In dev, might be at project root node_modules
    try {
      Database = require(path.join(__dirname, '..', 'node_modules', 'better-sqlite3'));
    } catch (e2) {
      console.error('[db] better-sqlite3 not found. Run: npm install better-sqlite3');
      throw new Error('SQLite not available — better-sqlite3 is not installed');
    }
  }

  if (!fs.existsSync(DB_DIR)) fs.mkdirSync(DB_DIR, { recursive: true });

  db = new Database(DB_PATH, { verbose: process.env.WANGARI_DEV ? console.log : null });
  db.pragma('journal_mode = WAL');
  db.pragma('foreign_keys = ON');
  db.pragma('busy_timeout = 5000');

  createTables(db);
  console.log('[db] SQLite initialized at', DB_PATH);

  return db;
}

function createTables(db) {
  db.exec(`
    -- Track schema version for migrations
    CREATE TABLE IF NOT EXISTS _schema_version (
      version INTEGER PRIMARY KEY,
      applied_at TEXT DEFAULT (datetime('now'))
    );

    -- Users table
    CREATE TABLE IF NOT EXISTS users (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      username TEXT UNIQUE NOT NULL,
      email TEXT UNIQUE,
      password TEXT NOT NULL,
      full_name TEXT,
      phone TEXT,
      role TEXT DEFAULT 'customer',
      is_active INTEGER DEFAULT 1,
      last_login TEXT,
      created_at TEXT DEFAULT (datetime('now')),
      updated_at TEXT DEFAULT (datetime('now')),
      _synced INTEGER DEFAULT 0,
      _deleted INTEGER DEFAULT 0,
      _sync_id TEXT,
      _last_sync TEXT
    );

    -- Products table
    CREATE TABLE IF NOT EXISTS products (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      name TEXT NOT NULL,
      category TEXT,
      price REAL DEFAULT 0,
      unit TEXT DEFAULT 'piece',
      quantity INTEGER DEFAULT 0,
      min_stock INTEGER DEFAULT 0,
      description TEXT,
      image_url TEXT,
      is_active INTEGER DEFAULT 1,
      created_at TEXT DEFAULT (datetime('now')),
      updated_at TEXT DEFAULT (datetime('now')),
      _synced INTEGER DEFAULT 0,
      _deleted INTEGER DEFAULT 0,
      _sync_id TEXT,
      _last_sync TEXT
    );

    -- Animals table
    CREATE TABLE IF NOT EXISTS animals (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      tag_number TEXT,
      name TEXT,
      species TEXT DEFAULT 'chicken',
      breed TEXT,
      gender TEXT,
      dob TEXT,
      status TEXT DEFAULT 'active',
      weight REAL,
      location TEXT,
      notes TEXT,
      created_at TEXT DEFAULT (datetime('now')),
      updated_at TEXT DEFAULT (datetime('now')),
      _synced INTEGER DEFAULT 0,
      _deleted INTEGER DEFAULT 0,
      _sync_id TEXT,
      _last_sync TEXT
    );

    -- Inventory table
    CREATE TABLE IF NOT EXISTS inventory (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      product_id INTEGER,
      quantity INTEGER DEFAULT 0,
      unit_cost REAL DEFAULT 0,
      supplier TEXT,
      batch_number TEXT,
      expiry_date TEXT,
      location TEXT,
      created_at TEXT DEFAULT (datetime('now')),
      updated_at TEXT DEFAULT (datetime('now')),
      _synced INTEGER DEFAULT 0,
      _deleted INTEGER DEFAULT 0,
      _sync_id TEXT,
      _last_sync TEXT,
      FOREIGN KEY (product_id) REFERENCES products(id)
    );

    -- Orders table
    CREATE TABLE IF NOT EXISTS orders (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER,
      customer_name TEXT,
      customer_phone TEXT,
      total_amount REAL DEFAULT 0,
      status TEXT DEFAULT 'pending',
      payment_method TEXT,
      notes TEXT,
      created_at TEXT DEFAULT (datetime('now')),
      updated_at TEXT DEFAULT (datetime('now')),
      _synced INTEGER DEFAULT 0,
      _deleted INTEGER DEFAULT 0,
      _sync_id TEXT,
      _last_sync TEXT,
      FOREIGN KEY (user_id) REFERENCES users(id)
    );

    -- Order items
    CREATE TABLE IF NOT EXISTS order_items (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      order_id INTEGER NOT NULL,
      product_id INTEGER,
      product_name TEXT,
      quantity INTEGER DEFAULT 1,
      unit_price REAL DEFAULT 0,
      subtotal REAL DEFAULT 0,
      _synced INTEGER DEFAULT 0,
      _deleted INTEGER DEFAULT 0,
      _sync_id TEXT,
      FOREIGN KEY (order_id) REFERENCES orders(id),
      FOREIGN KEY (product_id) REFERENCES products(id)
    );

    -- Financial records
    CREATE TABLE IF NOT EXISTS financial_records (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      type TEXT NOT NULL,
      category TEXT,
      amount REAL DEFAULT 0,
      description TEXT,
      transaction_date TEXT,
      recorded_by INTEGER,
      receipt_number TEXT,
      created_at TEXT DEFAULT (datetime('now')),
      updated_at TEXT DEFAULT (datetime('now')),
      _synced INTEGER DEFAULT 0,
      _deleted INTEGER DEFAULT 0,
      _sync_id TEXT,
      _last_sync TEXT,
      FOREIGN KEY (recorded_by) REFERENCES users(id)
    );

    -- Tasks / Activity log
    CREATE TABLE IF NOT EXISTS tasks (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      title TEXT NOT NULL,
      description TEXT,
      assigned_to INTEGER,
      status TEXT DEFAULT 'pending',
      priority TEXT DEFAULT 'normal',
      due_date TEXT,
      completed_at TEXT,
      created_at TEXT DEFAULT (datetime('now')),
      updated_at TEXT DEFAULT (datetime('now')),
      _synced INTEGER DEFAULT 0,
      _deleted INTEGER DEFAULT 0,
      _sync_id TEXT,
      FOREIGN KEY (assigned_to) REFERENCES users(id)
    );

    -- LPOs (Local Purchase Orders)
    CREATE TABLE IF NOT EXISTS lpos (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      lpo_number TEXT UNIQUE,
      supplier TEXT,
      total_amount REAL DEFAULT 0,
      status TEXT DEFAULT 'draft',
      items TEXT,
      notes TEXT,
      created_by INTEGER,
      created_at TEXT DEFAULT (datetime('now')),
      updated_at TEXT DEFAULT (datetime('now')),
      _synced INTEGER DEFAULT 0,
      _deleted INTEGER DEFAULT 0,
      _sync_id TEXT,
      FOREIGN KEY (created_by) REFERENCES users(id)
    );

    -- Sync metadata
    CREATE TABLE IF NOT EXISTS _sync_meta (
      table_name TEXT PRIMARY KEY,
      last_push TEXT,
      last_pull TEXT,
      server_version INTEGER DEFAULT 0,
      local_version INTEGER DEFAULT 0
    );

    -- Indexes for performance
    CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);
    CREATE INDEX IF NOT EXISTS idx_users_active ON users(is_active);
    CREATE INDEX IF NOT EXISTS idx_products_category ON products(category);
    CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status);
    CREATE INDEX IF NOT EXISTS idx_orders_user ON orders(user_id);
    CREATE INDEX IF NOT EXISTS idx_financial_type ON financial_records(type);
    CREATE INDEX IF NOT EXISTS idx_financial_date ON financial_records(transaction_date);
    CREATE INDEX IF NOT EXISTS idx_animals_species ON animals(species);
    CREATE INDEX IF NOT EXISTS idx_animals_status ON animals(status);
    CREATE INDEX IF NOT EXISTS idx_tasks_status ON tasks(status);
    CREATE INDEX IF NOT EXISTS idx_tasks_assigned ON tasks(assigned_to);
  `);
}

// ─────────────────────────────────────────────────────────────────────────────
// GENERIC CRUD OPERATIONS
// ─────────────────────────────────────────────────────────────────────────────

function insert(table, data) {
  const conn = getDatabase();
  const now = new Date().toISOString();
  const record = { ...data, created_at: now, updated_at: now, _synced: 0, _deleted: 0 };

  const columns = Object.keys(record);
  const placeholders = columns.map(() => '?').join(', ');
  const sql = `INSERT INTO ${table} (${columns.join(', ')}) VALUES (${placeholders})`;

  const result = conn.prepare(sql).run(...Object.values(record));
  return { id: result.lastInsertRowid, ...record };
}

function update(table, id, data) {
  const conn = getDatabase();
  const record = { ...data, updated_at: new Date().toISOString(), _synced: 0 };

  const setClauses = Object.keys(record).map(k => `${k} = ?`).join(', ');
  const sql = `UPDATE ${table} SET ${setClauses} WHERE id = ?`;

  conn.prepare(sql).run(...Object.values(record), id);
  return findById(table, id);
}

function remove(table, id) {
  const conn = getDatabase();
  // Soft delete — mark for sync
  conn.prepare(`UPDATE ${table} SET _deleted = 1, _synced = 0, updated_at = datetime('now') WHERE id = ?`).run(id);
  return { id, deleted: true };
}

function findById(table, id) {
  const conn = getDatabase();
  return conn.prepare(`SELECT * FROM ${table} WHERE id = ? AND _deleted = 0`).get(id) || null;
}

function findAll(table, { where = '', params = [], orderBy = 'id DESC', limit = 100, offset = 0 } = {}) {
  const conn = getDatabase();
  const whereClause = where ? `WHERE _deleted = 0 AND ${where}` : 'WHERE _deleted = 0';
  const sql = `SELECT * FROM ${table} ${whereClause} ORDER BY ${orderBy} LIMIT ? OFFSET ?`;
  return conn.prepare(sql).all(...params, limit, offset);
}

function count(table, { where = '', params = [] } = {}) {
  const conn = getDatabase();
  const whereClause = where ? `WHERE _deleted = 0 AND ${where}` : 'WHERE _deleted = 0';
  const sql = `SELECT COUNT(*) as total FROM ${table} ${whereClause}`;
  return conn.prepare(sql).get(...params).total;
}

function search(table, columns, query, { limit = 50 } = {}) {
  const conn = getDatabase();
  const likeClauses = columns.map(c => `${c} LIKE ?`).join(' OR ');
  const params = columns.map(() => `%${query}%`);
  const sql = `SELECT * FROM ${table} WHERE _deleted = 0 AND (${likeClauses}) ORDER BY id DESC LIMIT ?`;
  return conn.prepare(sql).all(...params, limit);
}

// ─────────────────────────────────────────────────────────────────────────────
// BULK OPERATIONS (for sync)
// ─────────────────────────────────────────────────────────────────────────────

function bulkUpsert(table, records, serverSide = false) {
  const conn = getDatabase();
  const now = new Date().toISOString();
  let inserted = 0, updated = 0;

  const upsert = conn.transaction((rows) => {
    for (const row of rows) {
      const existing = row.id
        ? conn.prepare(`SELECT id, updated_at FROM ${table} WHERE id = ?`).get(row.id)
        : null;

      const syncFlag = serverSide ? 1 : 0;
      const data = { ...row, _synced: syncFlag, _deleted: 0, _last_sync: now };

      if (existing) {
        // Check for conflict: local changed after server last sync
        if (serverSide && existing._synced === 0) {
          // Conflict — local has unsynced changes, prefer local
          continue;
        }
        const setClauses = Object.keys(data).map(k => `${k} = ?`).join(', ');
        conn.prepare(`UPDATE ${table} SET ${setClauses} WHERE id = ?`).run(...Object.values(data), row.id);
        updated++;
      } else {
        const columns = Object.keys(data);
        const placeholders = columns.map(() => '?').join(', ');
        conn.prepare(`INSERT OR REPLACE INTO ${table} (${columns.join(', ')}) VALUES (${placeholders})`).run(...Object.values(data));
        inserted++;
      }
    }
  });

  upsert(records);
  return { inserted, updated, total: records.length };
}

function getUnsynced(table) {
  const conn = getDatabase();
  return conn.prepare(`SELECT * FROM ${table} WHERE _synced = 0 AND _deleted = 0`).all();
}

function getDeleted(table) {
  const conn = getDatabase();
  return conn.prepare(`SELECT * FROM ${table} WHERE _deleted = 1 AND _synced = 0`).all();
}

function markSynced(table, ids) {
  const conn = getDatabase();
  if (!ids.length) return;
  const placeholders = ids.map(() => '?').join(',');
  conn.prepare(`UPDATE ${table} SET _synced = 1, _last_sync = datetime('now') WHERE id IN (${placeholders})`).run(...ids);
}

function markDeletedSynced(table, ids) {
  const conn = getDatabase();
  if (!ids.length) return;
  const placeholders = ids.map(() => '?').join(',');
  conn.prepare(`DELETE FROM ${table} WHERE id IN (${placeholders}) AND _deleted = 1`).run(...ids);
}

// ─────────────────────────────────────────────────────────────────────────────
// SYNC METADATA
// ─────────────────────────────────────────────────────────────────────────────

function getSyncMeta(tableName) {
  const conn = getDatabase();
  return conn.prepare('SELECT * FROM _sync_meta WHERE table_name = ?').get(tableName) || null;
}

function setSyncMeta(tableName, data) {
  const conn = getDatabase();
  const existing = getSyncMeta(tableName);
  if (existing) {
    conn.prepare('UPDATE _sync_meta SET last_push = ?, last_pull = ?, server_version = ?, local_version = ? WHERE table_name = ?')
      .run(data.last_push, data.last_pull, data.server_version, data.local_version, tableName);
  } else {
    conn.prepare('INSERT INTO _sync_meta (table_name, last_push, last_pull, server_version, local_version) VALUES (?, ?, ?, ?, ?)')
      .run(tableName, data.last_push, data.last_pull, data.server_version, data.local_version);
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// AGGREGATE QUERIES (for dashboard)
// ─────────────────────────────────────────────────────────────────────────────

function getStats() {
  const conn = getDatabase();
  return {
    users: conn.prepare('SELECT COUNT(*) as c FROM users WHERE _deleted = 0').get().c,
    active_users: conn.prepare('SELECT COUNT(*) as c FROM users WHERE _deleted = 0 AND is_active = 1').get().c,
    products: conn.prepare('SELECT COUNT(*) as c FROM products WHERE _deleted = 0').get().c,
    animals: conn.prepare('SELECT COUNT(*) as c FROM animals WHERE _deleted = 0').get().c,
    orders: conn.prepare('SELECT COUNT(*) as c FROM orders WHERE _deleted = 0').get().c,
    pending_orders: conn.prepare("SELECT COUNT(*) as c FROM orders WHERE _deleted = 0 AND status = 'pending'").get().c,
    revenue: conn.prepare("SELECT COALESCE(SUM(amount), 0) as total FROM financial_records WHERE _deleted = 0 AND type = 'income'").get().total,
    unsynced: {
      users: conn.prepare('SELECT COUNT(*) as c FROM users WHERE _synced = 0').get().c,
      products: conn.prepare('SELECT COUNT(*) as c FROM products WHERE _synced = 0').get().c,
      orders: conn.prepare('SELECT COUNT(*) as c FROM orders WHERE _synced = 0').get().c,
      animals: conn.prepare('SELECT COUNT(*) as c FROM animals WHERE _synced = 0').get().c,
    }
  };
}

function getDashboardData() {
  const conn = getDatabase();
  const stats = getStats();
  const recentOrders = conn.prepare("SELECT * FROM orders WHERE _deleted = 0 ORDER BY created_at DESC LIMIT 5").all();
  const lowStock = conn.prepare("SELECT * FROM products WHERE _deleted = 0 AND quantity <= min_stock AND min_stock > 0 ORDER BY quantity ASC LIMIT 5").all();
  const pendingTasks = conn.prepare("SELECT * FROM tasks WHERE _deleted = 0 AND status != 'completed' ORDER BY due_date ASC LIMIT 5").all();
  return { stats, recentOrders, lowStock, pendingTasks };
}

// ─────────────────────────────────────────────────────────────────────────────
// UTILITIES
// ─────────────────────────────────────────────────────────────────────────────

function vacuum() {
  const conn = getDatabase();
  conn.pragma('vacuum');
}

function backup(backupPath) {
  const conn = getDatabase();
  const backup = require('better-sqlite3')(backupPath);
  conn.backup(backupPath).then(() => {
    backup.close();
    console.log('[db] Backup created at', backupPath);
  });
}

function closeDatabase() {
  if (db) {
    db.close();
    db = null;
  }
}

module.exports = {
  getDatabase,
  insert,
  update,
  remove,
  findById,
  findAll,
  count,
  search,
  bulkUpsert,
  getUnsynced,
  getDeleted,
  markSynced,
  markDeletedSynced,
  getSyncMeta,
  setSyncMeta,
  getStats,
  getDashboardData,
  vacuum,
  backup,
  closeDatabase,
  DB_PATH,
};
