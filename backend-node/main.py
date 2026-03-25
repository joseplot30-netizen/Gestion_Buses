from fastapi import FastAPI, Depends, Form, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from sqlalchemy.orm import Session
import models
from database import engine, get_db
from datetime import datetime

# Crea las tablas si no existen (aunque ya tienes el SQL)
models.Base.metadata.create_all(bind=engine)

app = FastAPI(title="AutoSoft Corp - API Central")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

# --- NUEVO: OBTENER ESTADO DE BUSES (El que te faltaba) ---
@app.get("/buses-status")
def obtener_buses_status(db: Session = Depends(get_db)):
    # Según tu SQL, la tabla se llama 'buses'
    return db.query(models.Bus).all()

# --- LOGIN DUAL CORREGIDO ---
@app.post("/login")
def login(
    username: str = Form(None),
    codigo_unidad: str = Form(None),
    codigo_bus: str = Form(None),
    password: str = Form(...),
    db: Session = Depends(get_db)
):
    login_key = username or codigo_unidad or codigo_bus
    if not login_key:
        raise HTTPException(status_code=400, detail="username, codigo_unidad o codigo_bus son requeridos")

    # 1. Buscar admin por username
    admin = db.query(models.Admin).filter(models.Admin.username == login_key).first()
    if admin and admin.password == password:
        return {"status": "success", "is_admin": True, "redirect_to": "admin.php", "nombre": admin.nombre}

    # 2. Buscar chofer por username
    user = db.query(models.User).filter(models.User.username == login_key).first()

    # 3. Si no lo encontramos, buscar por código de bus (o unidad) en la tabla buses
    if not user:
        bus = db.query(models.Bus).filter(models.Bus.codigo_bus == login_key).first()
        if bus:
            user = db.query(models.User).filter(models.User.bus_asignado_id == bus.id).first()

    if user and user.password == password:
        bus = db.query(models.Bus).filter(models.Bus.id == user.bus_asignado_id).first() if user.bus_asignado_id else None
        return {
            "status": "success",
            "is_admin": False,
            "codigo_unidad": bus.codigo_bus if bus else None,
            "nombre": user.nombre,
            "redirect_to": "bus_status.php"
        }

    raise HTTPException(status_code=401, detail="Credenciales incorrectas")

# --- REGISTRO UNIVERSAL ---
@app.post("/register-universal")
def register_universal(
    nombre: str = Form(...),
    apellido: str = Form(...),
    username: str = Form(...),
    email: str = Form(...),
    password: str = Form(...),
    es_admin: bool = Form(False),
    bus_id: int = Form(None),
    db: Session = Depends(get_db)
):
    # Verificación de existencia
    exists_u = db.query(models.User).filter(models.User.username == username).first()
    exists_a = db.query(models.Admin).filter(models.Admin.username == username).first()
    
    if exists_u or exists_a:
        raise HTTPException(status_code=400, detail="El nombre de usuario ya existe")

    if es_admin:
        nuevo = models.Admin(
            nombre=nombre, apellido=apellido, username=username, 
            email=email, password=password, nivel_acceso="SuperAdmin"
        )
    else:
        if not bus_id:
            raise HTTPException(status_code=400, detail="Debe asignar un ID de bus")
        nuevo = models.User(
            nombre=nombre, apellido=apellido, username=username, 
            email=email, password=password, bus_asignado_id=bus_id
        )

    db.add(nuevo)
    db.commit()
    return {"status": "success"}

# ... (Tus otros imports se mantienen igual)

# --- NUEVO: OBTENER INCIDENTES PARA EL ADMIN ---
@app.get("/obtener-incidentes")
def obtener_incidentes(db: Session = Depends(get_db)):
    # Traemos los incidentes pendientes, ordenados por los más recientes
    return db.query(models.Incidente).filter(
        models.Incidente.estado_reporte == "pendiente"
    ).order_by(models.Incidente.fecha_reporte.desc()).all()

# --- NUEVO: MARCAR INCIDENTE COMO RESUELTO ---
# --- MARCAR INCIDENTE COMO RESUELTO Y NOTIFICAR AL BUS ---
@app.post("/resolver-incidente/{incidente_id}")
def resolver_incidente(
    incidente_id: int, 
    respuesta_admin: str = Form(...), 
    db: Session = Depends(get_db)
):
    incidente = db.query(models.Incidente).filter(models.Incidente.id == incidente_id).first()
    if not incidente:
        raise HTTPException(status_code=404, detail="No existe el reporte")
    
    # 1. Marcamos el incidente como resuelto
    incidente.estado_reporte = "resuelto"
    incidente.respuesta_admin = respuesta_admin 
    
    # 2. IMPORTANTE: Buscamos el bus y actualizamos SU mensaje_central
    # Esto es lo que el chofer consultará cada 3 segundos
    bus = db.query(models.Bus).filter(models.Bus.codigo_bus == incidente.vehiculo_id).first()
    if bus:
        bus.mensaje_central = respuesta_admin
    
    db.commit()
    return {"status": "success", "message": "Respuesta enviada al bus"}

@app.post("/reportar-incidente")
def reportar_incidente(
    vehiculo_id: str = Form(...), 
    descripcion: str = Form(...), 
    prioridad: str = Form("Alta"), # Por defecto Alta para que el admin lo note
    db: Session = Depends(get_db)
):
    nuevo_incidente = models.Incidente(
        vehiculo_id=vehiculo_id, 
        descripcion=descripcion,
        prioridad=prioridad,
        fecha_reporte=datetime.now(),
        estado_reporte="pendiente"
    )
    db.add(nuevo_incidente)
    db.commit()
    return {"status": "success"}

# ... (El resto de tus funciones: asignar-ruta, finalizar-ruta, etc., están bien)

# --- BORRAR INCIDENTE ---
@app.delete("/borrar-incidente/{incidente_id}")
def borrar_incidente(incidente_id: int, db: Session = Depends(get_db)):
    incidente = db.query(models.Incidente).filter(models.Incidente.id == incidente_id).first()
    if not incidente:
        raise HTTPException(status_code=404, detail="No existe el reporte")

    db.delete(incidente)
    db.commit()

    return {"status": "success", "message": "Incidente eliminado"}

# --- ASIGNAR/DESPACHAR RUTA ---
@app.post("/asignar-ruta")
def asignar_ruta(
    codigo_bus: str = Form(...), 
    origen: str = Form(...), 
    destino: str = Form(...), 
    db: Session = Depends(get_db)
):
    bus = db.query(models.Bus).filter(models.Bus.codigo_bus == codigo_bus).first()
    if not bus:
        raise HTTPException(status_code=404, detail="Bus no encontrado")

    bus.origen = origen
    bus.destino = destino
    bus.estado = 'servicio'
    bus.en_ruta = 1
    bus.ultima_actualizacion = datetime.now()
    
    db.commit()
    return {"status": "success", "message": f"Unidad {codigo_bus} en ruta"}

# --- FINALIZAR RUTA ---
@app.post("/finalizar-ruta")
def finalizar(codigo_bus: str = Form(...), db: Session = Depends(get_db)):
    bus = db.query(models.Bus).filter(models.Bus.codigo_bus == codigo_bus).first()
    if not bus:
        raise HTTPException(status_code=404, detail="Bus no encontrado")
    
    bus.en_ruta = 0
    bus.origen = "Parqueadero"
    bus.destino = "Disponible"
    bus.pasajeros = 0
    bus.estado = 'espera' # Regresa a estado espera según tu ENUM del SQL
    bus.ultima_actualizacion = datetime.now()
    
    db.commit()
    return {"status": "success"}

@app.post("/actualizar-bus")
def actualizar_bus(
    codigo_bus: str = Form(...), 
    estado: str = Form(...), 
    db: Session = Depends(get_db)
):
    # Buscamos por el campo 'codigo_bus' definido en tu clase Bus
    bus = db.query(models.Bus).filter(models.Bus.codigo_bus == codigo_bus).first()
    
    if not bus:
        raise HTTPException(status_code=404, detail="El bus con ese código no existe")
    
    # Actualizamos los campos según el modelo
    bus.estado = estado
    bus.ultima_actualizacion = datetime.now() 
    
    db.commit()
    db.refresh(bus) # Refrescamos para obtener los datos actualizados
    
    return {
        "status": "success", 
        "mensaje": f"Unidad {codigo_bus} actualizada a {estado}",
        "data": {
            "codigo": bus.codigo_bus,
            "nuevo_estado": bus.estado,
            "fecha": bus.ultima_actualizacion
        }
    }


@app.post("/mensaje-bus")
def enviar_mensaje_bus(
    codigo_bus: str = Form(...),
    mensaje: str = Form(...),
    db: Session = Depends(get_db)
):
    bus = db.query(models.Bus).filter(models.Bus.codigo_bus == codigo_bus).first()
    if not bus:
        raise HTTPException(status_code=404, detail="Bus no encontrado")

    bus.mensaje_central = mensaje
    db.commit()

    return {"status": "success", "message": "Mensaje enviado al bus"}

@app.get("/status")
def obtener_status():
    return {"status": "En línea"}

@app.get("/usuarios")
def obtener_usuarios(db: Session = Depends(get_db)):
    return db.query(models.User).all()

@app.put("/usuario/{user_id}")
def actualizar_usuario(
    user_id: int,
    nombre: str = Form(None),
    apellido: str = Form(None),
    username: str = Form(None),
    email: str = Form(None),
    password: str = Form(None),
    bus_asignado_id: int = Form(None),
    db: Session = Depends(get_db)
):
    user = db.query(models.User).filter(models.User.id == user_id).first()
    if not user:
        raise HTTPException(status_code=404, detail="Usuario no encontrado")

    if nombre is not None:
        user.nombre = nombre
    if apellido is not None:
        user.apellido = apellido
    if username is not None:
        user.username = username
    if email is not None:
        user.email = email
    if password is not None:
        user.password = password
    if bus_asignado_id is not None:
        user.bus_asignado_id = bus_asignado_id

    db.commit()
    db.refresh(user)
    return {"status": "success", "user": user}

@app.delete("/usuario/{user_id}")
def eliminar_usuario(user_id: int, db: Session = Depends(get_db)):
    user = db.query(models.User).filter(models.User.id == user_id).first()
    if not user:
        raise HTTPException(status_code=404, detail="Usuario no encontrado")
    
    db.delete(user)
    db.commit()
    return {"status": "success", "message": "Usuario eliminado"}

@app.post("/limpiar-mensaje-bus/{codigo_bus}")
def limpiar_mensaje_bus(codigo_bus: str, db: Session = Depends(get_db)):
    bus = db.query(models.Bus).filter(models.Bus.codigo_bus == codigo_bus).first()
    if bus:
        bus.mensaje_central = None
        db.commit()
        return {"status": "ok"}
    raise HTTPException(status_code=404, detail="Bus no encontrado")