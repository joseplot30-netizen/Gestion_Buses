from sqlalchemy import Column, Integer, String, Boolean, Text, DateTime, Float, ForeignKey
from sqlalchemy.orm import relationship
from datetime import datetime
from database import Base

# --- TABLA DE BUSES ---
class Bus(Base):
    __tablename__ = "buses"
    id = Column(Integer, primary_key=True, index=True)
    codigo_bus = Column(String(20), unique=True)
    placa = Column(String(15), unique=True)
    capacidad = Column(Integer, default=40)
    estado = Column(String(50), default="espera")
    pasajeros = Column(Integer, default=0)
    origen = Column(String(100), default="Parqueadero")
    destino = Column(String(100), default="Disponible")
    tiempo_llegada_estimado = Column(String(50), default="0 min")
    en_ruta = Column(Integer, default=0)
    ultima_actualizacion = Column(DateTime, default=datetime.now)
    mensaje_central = Column(Text, nullable=True)

    # Relación inversa: Un bus tiene un chofer (User)
    chofer_rel = relationship("User", back_populates="bus_rel", uselist=False)

# --- TABLA DE CHOFERES (USUARIOS) ---
class User(Base):
    __tablename__ = "users"
    id = Column(Integer, primary_key=True, index=True)
    nombre = Column(String(50))
    apellido = Column(String(50))
    username = Column(String(50), unique=True, index=True)
    email = Column(String(100), unique=True)
    password = Column(String(100))
    bus_asignado_id = Column(Integer, ForeignKey("buses.id"), nullable=True)
    fecha_registro = Column(DateTime, default=datetime.now)

    # Relación: Un chofer pertenece a un bus
    bus_rel = relationship("Bus", back_populates="chofer_rel")

# --- TABLA DE INCIDENTES ---
class Incidente(Base):
    __tablename__ = "reportes_incidentes"
    id = Column(Integer, primary_key=True, index=True)
    vehiculo_id = Column(String(50))
    descripcion = Column(Text)
    prioridad = Column(String(20))
    fecha_reporte = Column(DateTime, default=datetime.utcnow)
    # Columnas esenciales para que funcione el borrado/respuesta
    estado_reporte = Column(String(20), default="pendiente") 
    respuesta_admin = Column(Text, nullable=True)

# --- TABLA DE RUTAS/DESTINOS ---
class Destino(Base):
    __tablename__ = "destinos"
    id = Column(Integer, primary_key=True, index=True)
    nombre_ruta = Column(String(100))
    origen = Column(String(100))
    destino = Column(String(100))
    distancia_km = Column(Float)

# --- TABLA DE ADMINISTRADORES ---
class Admin(Base):
    __tablename__ = "users_admin"
    id = Column(Integer, primary_key=True, index=True)
    nombre = Column(String(50))
    apellido = Column(String(50))
    username = Column(String(50), unique=True, index=True)
    email = Column(String(100), unique=True)
    password = Column(String(100))
    nivel_acceso = Column(String(20), default="SuperAdmin")
    fecha_registro = Column(DateTime, default=datetime.now)