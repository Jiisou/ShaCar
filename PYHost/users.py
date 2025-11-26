import mysql.connector
from mysql.connector import Error
import os
from dotenv import load_dotenv
import pandas as pd
from utils import get_db_connection, valid_date

"""
Users (id, name, age, license_year)
"""

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



def select_all_users():
    conn = get_db_connection()
    if conn is None:
        return []

    try:
        query = "SELECT * FROM Users"
        df = pd.read_sql(query, conn)
        print(df)
        return df.to_dict("records")
    except Error as e:
        print("Error fetching users:", e)
    finally:
        conn.close()

def select_user_by_username(username):
    conn = get_db_connection()
    if conn is None:
        return None
    try:
        cur = conn.cursor(dictionary=True)
        query = "SELECT * FROM Users WHERE name = %s"
        cur.execute(query, (username,))
        user = cur.fetchone()

        print(user)
        return user

    except Error as e:
        print("Error while fetching user", e)
        return None

    finally:
        if conn.is_connected():
            cur.close()
            conn.close()

def insert_user(username, age, license_year):
    conn = get_db_connection()
    if conn is None:
        return False

    try:
        # 국내 면허 취득 연령: 17세 미만이면 추가 불가
        if age < 17:
            print("User is underage to have a license")
            return False

        cur = conn.cursor()

        # 중복 체크: name, age, license_year 모두 일치하면 중복으로 처리
        cur.execute(
            "SELECT COUNT(*) FROM Users WHERE name=%s AND age=%s AND license_year=%s",
            (username, age, license_year)
        )
        if cur.fetchone()[0] > 0:
            print("User already exists")
            return False

        cur.execute(
            "INSERT INTO Users (name, age, license_year) VALUES (%s, %s, %s)",
            (username, age, license_year)
        )
        conn.commit()
        print("User added")
        return True
    except Error as e:
        print("Error adding user:", e)
        return False
    finally:
        if cur:
            try:
                cur.close()
            except:
                pass
        if conn and conn.is_connected():
            try:
                conn.close()
            except:
                pass

# 사용자 정보 수정
def update_user(user_id, new_name=None, new_age=None, new_license_year=None):
    conn = get_db_connection()
    if conn is None:
        return False

    try:
        cur = conn.cursor()

        sql = "UPDATE Users SET "
        params = []

        if new_name is not None:
            sql += "name = %s, "
            params.append(new_name)

        if new_age is not None:
            sql += "age = %s, "
            params.append(new_age)

        if new_license_year is not None:
            sql += "license_year = %s, "
            params.append(new_license_year)

        # 업데이트할 필드가 하나도 없는 경우
        if not params:
            print("[UPDATE SKIPPED] No values provided.")
            return False

        sql = sql.rstrip(", ") + " WHERE id = %s"
        params.append(user_id)

        cur.execute(sql, tuple(params))
        conn.commit()

        print(f"[UPDATE OK] User (id) {user_id}:{new_name} updated.")
        return True

    except Error as e:
        print("[UPDATE ERROR]", e)
        return False
    finally:
        cur.close()
        conn.close()

# 사용자 삭제
def delete_user_by_username(username):
    conn = get_db_connection()
    if conn is None:
        return False
    try:
        cur = conn.cursor()
        query = "DELETE FROM Users WHERE name = %s"
        cur.execute(query, (username,))
        conn.commit()

        if cur.rowcount == 0:
            print("[DELETE] No such user:", username)
            return False

        print(f"[DELETE OK] User '{username}' deleted.")
        return True

    except Error as e:
        print("[DELETE ERROR]", e)
        return False
    finally:
        cur.close()
        conn.close()

if __name__ == "__main__":
    select_all_users()

    # INSERT
    insert_user("Alice", 15, 2018)
    # SELECT *
    select_user_by_username("Alice")

    # UPDATE
    update_user(1, new_name="Jisu Jang", new_age=25)
    # SELECT again
    select_user_by_username("Jisu Jang")

    # DELETE
    delete_user_by_username("James")
    # SELECT *
    select_all_users()
    select_user_by_username("James")