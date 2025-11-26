import mysql.connector
from mysql.connector import Error
import os
from dotenv import load_dotenv
from datetime import datetime
import pandas as pd
from utils import get_db_connection, valid_date

"""
Vehicles (id, type, registered_at)

+. type ENUM('Compact', 'MidSize', 'SUV', 'Truck', 'Electric')

"""

load_dotenv()

#신규 차량 등록
def insert_vehicle(vehicle_type, registered_at):
    if registered_at and not valid_date(registered_at):
        return False
    
    conn = get_db_connection()
    if conn is None:
        return False

    try: 
        cur = conn.cursor()

        # 중복 체크: type, registered_at 모두 일치하면 중복 처리
        cur.execute(
            "SELECT COUNT(*) FROM Vehicles WHERE type=%s AND registered_at=%s",
            (vehicle_type, registered_at)
        )
        if cur.fetchone()[0] > 0:
            print("Vehicle already exist")
            return False
        
        cur.execute(
            "INSERT INTO Vehicles (type, registered_at) VALUES (%s, %s)", 
            (vehicle_type, registered_at)
        )
        conn.commit()
        print("Vehicle added")
        return True

    except Error as e:
        print("Error adding vehicle:", e)
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

# 차량 정보 수정
def update_vehicle(vehicle_id, new_type=None, new_registered_at=None):
    if new_registered_at and not valid_date(new_registered_at):
        return False

    conn = get_db_connection()
    if conn is None:
        return False

    try:
        cur = conn.cursor()

        sql = "UPDATE Vehicles SET "
        params = []

        if new_type is not None:
            sql += "type = %s, "
            params.append(new_type)

        if new_registered_at is not None:
            sql += "registered_at = %s, "
            params.append(new_registered_at)

        # 업데이트할 필드가 하나도 없는 경우
        if not params:
            print("[UPDATE SKIPPED] No values provided.")
            return False

        sql = sql.rstrip(", ") + " WHERE id = %s"
        params.append(vehicle_id)

        cur.execute(sql, tuple(params))
        conn.commit()

        print(f"[UPDATE OK] Vehicle (id) {vehicle_id}:{new_type} updated.")
        return True

    except Error as e:
        print("[UPDATE ERROR]", e)
        return False
    finally:
        cur.close()
        conn.close()

def select_all_vehicles():
    conn = get_db_connection()
    if conn is None:
        return []

    try:
        # query = "SELECT * FROM Vehicles"
        # df = pd.read_sql(query, conn)
        # print(df)
        # return df.to_dict("records")

        cur = conn.cursor()
        cur.execute("SELECT * FROM Vehicles")
        rows = cur.fetchall()

        print("\n=== Vehicles Table ===")
        for row in rows:
            print(row)

        return rows

    except Error as e:
        print("Error fetching users:", e)
    finally:
        conn.close()

def delete_vehicle(vehicle_id):
    conn = get_db_connection()
    if conn is None:
        return False

    try:
        cur = conn.cursor()
        sql = "DELETE FROM Vehicles WHERE id = %s"
        cur.execute(sql, (vehicle_id,))
        conn.commit()

        if cur.rowcount == 0:
            print(f"[DELETE] Vehicle (id) {vehicle_id} not found.")
            return False

        print(f"[DELETE OK] Vehicle (id) {vehicle_id} deleted.")
        return True
    
    except Error as e:
        print("[DELETE ERROR]", e)
        return False
    finally:
        cur.close()
        conn.close()

if __name__ == "__main__":
    select_all_vehicles()

    #INSERT
    insert_vehicle("Truck", '2023-12-21')
    select_all_vehicles()

    update_vehicle(1, new_registered_at='2020-02-01')
    select_all_vehicles()

    #DELETE
    delete_vehicle(11)
    select_all_vehicles()