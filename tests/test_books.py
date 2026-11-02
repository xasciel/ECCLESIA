"""Regresiones de las consultas de libros, sin usar credenciales reales.
Ejecutar: python -m unittest discover -s tests
"""
import re
import sqlite3
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]


def query(filename, prefix):
    source = (ROOT / filename).read_text()
    queries = re.findall(r'->prepare\(([\'\"])(.*?)\1\)', source, re.S)
    return next(sql for _, sql in queries if sql.lstrip().startswith(prefix))


class BooksTest(unittest.TestCase):
    def setUp(self):
        self.db = sqlite3.connect(':memory:')
        self.db.executescript('''
            CREATE TABLE books (id INTEGER PRIMARY KEY, church_id INTEGER,
                sacrament TEXT, book_number TEXT, year_start INTEGER,
                year_end INTEGER, notes TEXT, division TEXT, parent_book_id INTEGER);
            CREATE TABLE records (id INTEGER PRIMARY KEY, church_id INTEGER,
                book_id INTEGER, person_id INTEGER, deleted_at TEXT,
                record_number TEXT, folio TEXT, sacrament_date TEXT);
            CREATE TABLE people (id INTEGER PRIMARY KEY, church_id INTEGER,
                first_names TEXT, last_names TEXT);
            INSERT INTO books VALUES
                (1,1,'bautismo','1',2000,2020,'','',NULL),
                (2,1,'bautismo','1',2000,2020,'','A',1),
                (3,2,'bautismo','9',2000,2020,'','',NULL);
            INSERT INTO people VALUES (1,1,'Ana','López'), (2,2,'Luis','Pérez');
            INSERT INTO records VALUES
                (1,1,1,1,NULL,'1','1','2010-01-01'),
                (2,1,2,1,NULL,'2','2','2011-01-01'),
                (3,1,1,1,'2020-01-01','3','3','2012-01-01'),
                (4,2,3,2,NULL,'4','4','2013-01-01');
        ''')

    def tearDown(self):
        self.db.close()

    def test_flat_list_includes_existing_divisions_and_own_counts(self):
        rows = self.db.execute(query('books.php', 'SELECT b.*'), [1]).fetchall()
        self.assertEqual({row[0]: row[-1] for row in rows}, {1: 1, 2: 1})

    def test_detail_only_shows_selected_books_active_records(self):
        sql = query('ver_libro.php', 'SELECT\n        r.id')
        self.assertEqual([r[0] for r in self.db.execute(sql, [1, 1])], [1])
        self.assertEqual([r[0] for r in self.db.execute(sql, [1, 2])], [2])
        self.assertEqual(list(self.db.execute(sql, [1, 3])), [])

    def test_new_book_has_no_parent(self):
        self.db.execute(query('agregar.php', 'INSERT INTO books'),
                        [1, 'bautismo', '2', 2000, 2020, '', '', None])
        self.assertIsNone(self.db.execute('SELECT parent_book_id FROM books WHERE id=4').fetchone()[0])

    def test_edit_makes_existing_division_independent_and_preserves_records(self):
        sql = query('agregar.php', 'UPDATE books')
        self.db.execute(sql, ['1 A', 'bautismo', 2000, 2020, '', 2, 1])
        self.assertEqual(self.db.execute('SELECT parent_book_id,division FROM books WHERE id=2').fetchone(), (None, 'A'))
        self.assertEqual(self.db.execute('SELECT book_id FROM records WHERE id=2').fetchone()[0], 2)
        self.db.execute(sql, ['changed', 'bautismo', 2000, 2020, '', 3, 1])
        self.assertEqual(self.db.execute('SELECT book_number FROM books WHERE id=3').fetchone()[0], '9')

    def test_deletion_preserves_former_divisions(self):
        self.db.execute('DELETE FROM records WHERE book_id=1')
        self.db.execute(query('books.php', 'UPDATE books'), [1, 1])
        self.db.execute(query('books.php', 'DELETE FROM books'), [1, 1])
        self.assertEqual(self.db.execute('SELECT parent_book_id FROM books WHERE id=2').fetchone(), (None,))
        self.assertEqual(self.db.execute('SELECT book_id FROM records WHERE id=2').fetchone()[0], 2)

    def test_deletion_guard_counts_archived_records_too(self):
        self.assertEqual(self.db.execute(query('books.php', 'SELECT COUNT(*)'), [1, 1]).fetchone()[0], 2)


if __name__ == '__main__':
    unittest.main()
