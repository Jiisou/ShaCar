import mysql.connector
from mysql.connector import Error
import os
from dotenv import load_dotenv
from datetime import datetime
from utils import valid_date, get_db_connection

"""
    Users (id, name, age, license_year)
    Vehicles (id, type, registered_at)
    Insurance_Plan (id, type, daily_fee, deductible_amount, vehicle_class, min_driver_age, min_license_years, name)
    Rental_Reservation (id, uid, vid, iid, start_date, end_date)

"""

def insert_reservation(user_id, vehicle_id, insurance_id, start_date, end_date):
    # 날짜 검증
    if start_date and not valid_date(start_date):
        return False
    if end_date and not valid_date(end_date):
        return False
    
    conn = get_db_connection()
    if conn is None:
        return False

    try: 
        cur = conn.cursor()

        # 1. 정보 조회 및 조건 검사
        # User 정보 조회
        cur.execute("SELECT age, license_year FROM Users WHERE id = %s", (user_id,))
        user_row = cur.fetchone()
        if not user_row:
            print("[INSERT ERROR] User not found")
            return False
        user_age, user_license_year = user_row

        # Vehicle 정보 조회
        cur.execute("SELECT type FROM Vehicles WHERE id = %s", (vehicle_id,))
        vehicle_row = cur.fetchone()
        if not vehicle_row:
            print("[INSERT ERROR] Vehicle not found")
            return False
        vehicle_type = vehicle_row[0]

        # Insurance 정보 조회
        cur.execute("SELECT vehicle_class, min_driver_age, min_license_years FROM Insurance_Plan WHERE id = %s", (insurance_id,))
        insurance_row = cur.fetchone()
        if not insurance_row:
            print("[INSERT ERROR] Insurance plan not found")
            return False
        ins_vehicle_class, ins_min_age, ins_min_license_years = insurance_row

        # 2. 비즈니스 로직 검사
        # 나이 검사
        if user_age < ins_min_age:
            print(f"[INSERT ERROR] User age ({user_age}) is less than minimum required age ({ins_min_age})")
            return False
        
        # 면허 취득 기간 검사
        current_year = datetime.now().year
        license_duration = current_year - user_license_year
        if license_duration < ins_min_license_years:
            print(f"[INSERT ERROR] License duration ({license_duration} years) is less than minimum required ({ins_min_license_years} years)")
            return False
            
        # 차량 타입 일치 검사
        if vehicle_type != ins_vehicle_class:
            print(f"[INSERT ERROR] Vehicle type ({vehicle_type}) does not match insurance vehicle class ({ins_vehicle_class})")
            return False

        # 3. 중복 체크: vid, start_date, end_date가 모두 일치하면 중복 처리 (문제 요구사항 1번)
        # (vehicle_id, end_date, start_date) 중복 방지라고 명시됨.
        cur.execute(
            "SELECT COUNT(*) FROM Rental_Reservation WHERE uid=%s AND vid=%s AND iid=%s AND start_date=%s AND end_date=%s",
            (user_id, vehicle_id, insurance_id, start_date, end_date)
        )
        if cur.fetchone()[0] > 0:
            print("[INSERT SKIPPED] Reservation already exist")
            return False
        
        # 4. 새로운 예약 Insert
        cur.execute(
            "INSERT INTO Rental_Reservation (uid, vid, iid, start_date, end_date) VALUES (%s, %s, %s, %s, %s)", 
            (user_id, vehicle_id, insurance_id, start_date, end_date)
        )
        conn.commit()
        print("[INSERT OK] Reservation added")
        return True

    except Error as e:
        print("[INSERT ERROR] Error adding reservation:", e)
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

def select_all_reservations():
    conn = get_db_connection()
    if conn is None:
        return None
    
    try:
        cur = conn.cursor()
        cur.execute("SELECT * FROM Rental_Reservation")
        rows = cur.fetchall()
        
        print("\n=== Rental_Reservation Table ===")
        for row in rows:
            print(row)

        return rows

    except Error as e:
        print("[SELECT ERROR]", e)
        return []
    finally:
        conn.close()

def update_reservation(res_id, new_start_date=None, new_end_date=None):
    if new_start_date and not valid_date(new_start_date):
        return False
    if new_end_date and not valid_date(new_end_date):
        return False

    conn = get_db_connection()
    if conn is None:
        return False

    try:
        conn.start_transaction()
        cur = conn.cursor()

        # 기존 예약 정보 가져오기
        cur.execute("""
            SELECT vid, start_date, end_date
            FROM Rental_Reservation
            WHERE id = %s
            FOR UPDATE
        """, (res_id,))
        row = cur.fetchone()
        if not row:
            print("[UPDATE ERROR] Reservation not found.")
            conn.rollback()
            return False

        vehicle_id, old_start, old_end = row

        new_start = new_start_date or old_start
        new_end = new_end_date or old_end

        # 새로운 렌탈 일정 중복 검사
        cur.execute("""
            SELECT id FROM Rental_Reservation
            WHERE vid = %s
              AND id != %s
              AND start_date <= %s
              AND end_date >= %s
            FOR UPDATE
        """, (vehicle_id, res_id, new_end, new_start))

        conflict = cur.fetchone()
        if conflict:
            print(f"[UPDATE CONFLICT] Overlaps with reservation {conflict[0]}.")
            conn.rollback()
            return False

        # UPDATE
        cur.execute("""
            UPDATE Rental_Reservation
            SET start_date = %s, end_date = %s
            WHERE id = %s
        """, (new_start, new_end, res_id))

        conn.commit()
        print("[UPDATE OK] Reservation updated.")
        return True

    except Error as e:
        conn.rollback()
        print("[UPDATE ERROR]", e)
        return False

    finally:
        conn.close()


def delete_reservation(res_id):
    conn = get_db_connection()
    if conn is None:
        return False

    try:
        cur = conn.cursor()
        cur.execute("DELETE FROM Rental_Reservation WHERE id = %s", (res_id,))
        conn.commit()

        if cur.rowcount == 0:
            print(f"[DELETE] Reservation {res_id} not found.")
            return False

        print(f"[DELETE OK] Reservation {res_id} deleted.")
        return True

    except Error as e:
        print("[DELETE ERROR]", e)
        return False

    finally:
        conn.close()


def select_reservation_by_filters(
    reservation_id=None,
    user_id=None,
    vehicle_id=None,
    insurance_id=None,
    start_date=None,
    end_date=None
):
    conn = get_db_connection()
    if conn is None:
        return []

    try:
        cur = conn.cursor()

        sql = "SELECT * FROM Rental_Reservation WHERE 1=1"
        params = []

        if reservation_id is not None:
            sql += " AND id = %s"
            params.append(reservation_id)

        if user_id is not None:
            sql += " AND uid = %s"
            params.append(user_id)

        if vehicle_id is not None:
            sql += " AND vid = %s"
            params.append(vehicle_id)

        if insurance_id is not None:
            sql += " AND iid = %s"
            params.append(insurance_id)

        if start_date is not None:
            sql += " AND start_date >= %s"
            params.append(start_date)

        if end_date is not None:
            sql += " AND end_date <= %s"
            params.append(end_date)

        cur.execute(sql, tuple(params))
        rows = cur.fetchall()

        print("\n=== Filtered Reservations ===")
        for row in rows:
            print(row)

        return rows

    except Error as e:
        print("[SELECT ERROR]", e)
        return []

    finally:
        if conn.is_connected():
            cur.close()
            conn.close()

if __name__ == "__main__":
    select_all_reservations()
    insert_reservation(4,10,10,"2025-12-23","2025-12-26")
    
    select_all_reservations()
    update_reservation(12,new_start_date="2025-12-23",new_end_date="2026-01-01")
    select_reservation_by_filters(reservation_id=12)

    delete_reservation(12)
    select_reservation_by_filters(reservation_id=12)
    
