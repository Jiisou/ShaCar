import mysql.connector
from mysql.connector import Error
import os
from dotenv import load_dotenv
import pandas as pd
"""
Users (id, name, age, license_year)
"""

# DB 접속
def get_db_connection():
    load_dotenv()
    try:
        connection = mysql.connector.connect(
            host="localhost",
            user=os.environ.get("DB_USER"),
            password=os.environ.get("DB_PW"),
            database=os.environ.get("DB_NAME")
        )
        return connection
    except Error as e:
        print("Error while connecting to MySQL", e)
        return None
    
# 사용자 조회
def get_all_users():
    conn = get_db_connection()
    if conn is None:
        return []
    try:
        cursor = conn.cursor(dictionary=True)
        query = "SELECT * FROM Users"
        cursor.execute(query)
        users = cursor.fetchall()

        data = pd.read_sql(query, conn)
        print(data)
        return users
    except Error as e:
        print("Error while fetching users", e)
        return []
    finally:
        if conn.is_connected():
            cursor.close()
            conn.close()

# 특정 사용자 조회
def get_user_by_username(username):
    conn = get_db_connection()
    if conn is None:
        return None
    try:
        cursor = conn.cursor(dictionary=True)
        query = "SELECT * FROM Users WHERE name = %s"
        cursor.execute(query, (username,))
        user = cursor.fetchone()

        data = pd.read_sql(query, conn, params=(username,))
        print(data)
        return user
    except Error as e:
        print("Error while fetching user", e)
        return None
    finally:
        if conn.is_connected():
            cursor.close()
            conn.close()

# 사용자 추가
def add_user(username, age, license_year):
    conn = get_db_connection()
    if conn is None:
        return False
    try:
        cursor = conn.cursor()
        query = "INSERT INTO Users (name, age, license_year) VALUES (%s, %s, %s)"
        cursor.execute(query, (username, age, license_year))
        conn.commit()


        return True
    except Error as e:
        print("Error while adding user", e)
        return False
    finally:
        if conn.is_connected():
            cursor.close()
            conn.close()

# 사용자 정보 수정
def update_user(user_id, new_name=None, new_age=None, new_license_year=None):
    conn = get_db_connection()
    if conn is None:
        return

    try:
        cur = conn.cursor()

        sql = "UPDATE Users SET "
        params = []

        if new_name:
            sql += "name = %s, "
            params.append(new_name)
        if new_age:
            sql += "age = %s, "
            params.append(new_age)
        if new_license_year:
            sql += "license_year = %s, "
            params.append(new_license_year)

        sql = sql.rstrip(", ") + " WHERE id = %s"
        params.append(user_id)

        cur.execute(sql, tuple(params))
        conn.commit()

        print(f"[UPDATE OK] User {user_id} updated.")

    except Error as e:
        print("[UPDATE ERROR]", e)

    finally:
        cur.close()
        conn.close()

# 사용자 삭제
def delete_user_by_username(username):
    conn = get_db_connection()
    if conn is None:
        return False
    try:
        cursor = conn.cursor()
        query = "DELETE FROM Users WHERE name = %s"
        cursor.execute(query, (username,))
        conn.commit()
        return True
    except Error as e:
        print("Error while deleting user", e)
        return False
    finally:
        if conn.is_connected():
            cursor.close()
            conn.close()

if __name__ == "__main__":

    # # INSERT
    # add_user("James", 29, 2018)
    # SELECT *
    get_all_users()

    # # UPDATE
    # update_user(1, new_name="jisu Jang", new_age=25)
    # # SELECT again
    # get_all_users()

    # # DELETE
    # delete_user_by_username("James")
    # # SELECT *
    # get_all_users()