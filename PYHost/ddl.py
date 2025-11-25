import mysql.connector
from dotenv import load_dotenv
import os

load_dotenv()

try:
    conn = mysql.connector.connect(
        host="localhost",
        user=os.environ.get("DB_USER"),
        password=os.environ.get("DB_PW"),
        database=os.environ.get("DB_NAME")
    )
    cur = conn.cursor()
    print("Succesfully connected with DB!!")

    with open("./schema.sql", "r", encoding="utf-8") as f:
        ddl_script = f.read()

    # 여러 쿼리가 하나의 문자열에 있을 때 세미콜론 단위로 split 후 개별 실행
    statements = ddl_script.split(";")

    for stmt in statements:
        s = stmt.strip()
        if s:
            print(f"[Executing] {s[:50]}...")
            cur.execute(s + ";")

    # for result in cur.execute(ddl_script, multi=True):
    #     pass

    conn.commit()
    cur.close()
    conn.close()


except mysql.connector.Error as err:
    print("Error occured:", err)

print("DDL executed successfully!")