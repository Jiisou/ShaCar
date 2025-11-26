import mysql.connector
from mysql.connector import Error
import os
from datetime import datetime
from dotenv import load_dotenv
load_dotenv()

def get_db_connection():
    try:
        return mysql.connector.connect(
            host="localhost",
            user=os.environ["DB_USER"],
            password=os.environ["DB_PW"],
            database=os.environ["DB_NAME"]
        )
    except Error as e:
        print(f"[Connection Error] {e}")
        return None


# 스키마가 없으면 schema.sql 적용해 생성하도록
def ensure_schema_loaded():
    conn = get_db_connection()
    if conn is None:
        return
    
    cur = conn.cursor()
    cur.execute("""
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = %s
          AND table_name = 'Users'
    """, (os.getenv("DB_NAME"),))
    
    exists = cur.fetchone()[0]

    if exists == 0:
        print("⚠️ Schema not found. Applying schema.sql...")
        with open("schema.sql") as f:
            script = f.read()
        for stmt in script.split(";"):
            s = stmt.strip()
            if s:
                cur.execute(s + ";")
        conn.commit()
        print("Schema created!")

# 숫자형인지 검증하고 타입을 보장함.
def valid_number(value, field_name):
    if value is None:
        return False
    try:
        int(value)
        return True
    except ValueError:
        print(f"[Invalid Number] {field_name} must be an integer. Given: {value}")
        return False


def valid_date(date_str):
    if not isinstance(date_str, str):
        print(f"[Invalid Type] Date must be a string in 'YYYY-MM-DD' format. Given: {type(date_str).__name__}")
        return False
    try:
        datetime.strptime(date_str, "%Y-%m-%d")
        return True
    except ValueError:
        print(f"[Invalid Date] Use YYYY-MM-DD. \nGiven: {date_str}")
        return False