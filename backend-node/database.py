from sqlalchemy import create_engine, text
from sqlalchemy.orm import sessionmaker, declarative_base

# URL de conexión a tu base de datos en XAMPP
SQLALCHEMY_DATABASE_URL = "mysql+pymysql://root@localhost:3306/proyectobbdd"

engine = create_engine(SQLALCHEMY_DATABASE_URL)
SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)
Base = declarative_base()

def check_db_connection():
    try:
        with engine.connect() as connection:
            connection.execute(text("SELECT 1"))
        print("\n" + "═"*40)
        print("✅ CORPORACIÓN AUTOMOTRIZ: CONEXIÓN EXITOSA")
        print("═"*40 + "\n")
    except Exception as e:
        print(f"\n❌ ERROR DE CONEXIÓN: {e}\n")

check_db_connection()

def get_db():
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()