import mysql.connector
from mysql.connector import Error
import os
from dotenv import load_dotenv
from datetime import datetime
from utils import get_db_connection,valid_number

"""
Insurance_Plan (
    id, 
    type, 
    daily_fee, 
    deductible_amount, -- 자기 부담금
    vehicle_class,     -- vehicle_class ENUM과 연결
    min_driver_age,    -- 가입 가능 최소 연령 
    min_license_years, -- 면허 취득 기간
    name)

"""

load_dotenv()


def select_all_insurance_plans():
    conn = get_db_connection()
    if conn is None:
        return []

    try:
        cur = conn.cursor()
        cur.execute("SELECT * FROM Insurance_Plan")
        rows = cur.fetchall()

        print("\n=== Insurance_Plan Table ===")
        for row in rows:
            print(row)

        return rows

    except Error as e:
        print("[SELECT ERROR]", e)
        return []

    finally:
        conn.close()

def insert_insurance_plan(
    insurance_type,
    daily_fee,
    deductible_amount,
    vehicle_class,
    min_driver_age,
    min_license_years,
    name
):
    # 숫자 보장
    if not valid_number(daily_fee, "daily_fee"):
        return False
    if not valid_number(deductible_amount, "deductible_amount"):
        return False
    if not valid_number(min_driver_age, "min_driver_age"):
        return False
    if not valid_number(min_license_years, "min_license_years"):
        return False


    conn = get_db_connection()
    if conn is None:
        return False

    try:
        cur = conn.cursor()

        # 중복 체크
        cur.execute("""
            SELECT COUNT(*)
            FROM Insurance_Plan
            WHERE type = %s AND vehicle_class = %s AND daily_fee = %s
        """, (insurance_type, vehicle_class, daily_fee))

        if cur.fetchone()[0] > 0:
            print("Insurance plan already exists.")
            return False

        sql = """
            INSERT INTO Insurance_Plan
            (type, daily_fee, deductible_amount, vehicle_class,
             min_driver_age, min_license_years, name)
            VALUES (%s, %s, %s, %s, %s, %s, %s)
        """

        cur.execute(sql, (
            insurance_type,
            daily_fee,
            deductible_amount,
            vehicle_class,
            min_driver_age,
            min_license_years,
            name
        ))
        conn.commit()

        print("[INSERT OK] Insurance plan added.")
        return True

    except Error as e:
        print("[INSERT ERROR]", e)
        return False

    finally:
        if cur:
            try: cur.close()
            except: pass
        if conn and conn.is_connected():
            try: conn.close()
            except: pass

def update_insurance_plan(
    plan_id,
    new_type=None,
    new_daily_fee=None,
    new_deductible_amount=None,
    new_vehicle_class=None,
    new_min_driver_age=None,
    new_min_license_years=None,
    new_name=None
):
    # 숫자형 보장
    for val, name in [
        (new_daily_fee, "daily_fee"),
        (new_deductible_amount, "deductible_amount"),
        (new_min_driver_age, "min_driver_age"),
        (new_min_license_years, "min_license_years")
    ]:
        if val is not None and not valid_number(val, name):
            return False

    conn = get_db_connection()
    if conn is None:
        return False

    try:
        cur = conn.cursor()

        sql = "UPDATE Insurance_Plan SET "
        params = []

        if new_type is not None:
            sql += "type = %s, "
            params.append(new_type)
        if new_daily_fee is not None:
            sql += "daily_fee = %s, "
            params.append(new_daily_fee)
        if new_deductible_amount is not None:
            sql += "deductible_amount = %s, "
            params.append(new_deductible_amount)
        if new_vehicle_class is not None:
            sql += "vehicle_class = %s, "
            params.append(new_vehicle_class)
        if new_min_driver_age is not None:
            sql += "min_driver_age = %s, "
            params.append(new_min_driver_age)
        if new_min_license_years is not None:
            sql += "min_license_years = %s, "
            params.append(new_min_license_years)
        if new_name is not None:
            sql += "name = %s, "
            params.append(new_name)

        if not params:
            print("[UPDATE SKIPPED] No values provided.")
            return False

        sql = sql.rstrip(", ") + " WHERE id = %s"
        params.append(plan_id)

        cur.execute(sql, tuple(params))
        conn.commit()

        print(f"[UPDATE OK] Insurance plan {plan_id} updated.")
        return True

    except Error as e:
        print("[UPDATE ERROR]", e)
        return False

    finally:
        cur.close()
        conn.close()

def delete_insurance_plan(plan_id):
    conn = get_db_connection()
    if conn is None:
        return False

    try:
        cur = conn.cursor()
        cur.execute("DELETE FROM Insurance_Plan WHERE id = %s", (plan_id,))
        conn.commit()

        if cur.rowcount == 0:
            print(f"[DELETE] Insurance plan {plan_id} not found.")
            return False

        print(f"[DELETE OK] Insurance plan {plan_id} deleted.")
        return True

    except Error as e:
        print("[DELETE ERROR]", e)
        return False

    finally:
        cur.close()
        conn.close()

if __name__ == "__main__":
    
    select_all_insurance_plans()
    insert_insurance_plan(
        "Basic",
        15000,
        5000,
        "Compact",
        21,
        1,
        "Basic insurance for compact cars"
    )

    update_insurance_plan(11, new_daily_fee=16000, new_name="Basic zero-deductible Plan")
    select_all_insurance_plans()

    delete_insurance_plan(11)
    select_all_insurance_plans()