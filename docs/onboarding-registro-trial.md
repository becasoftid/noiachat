# Onboarding: registro publico y trial basico

Ultima actualizacion: 2026-06-19

Este documento describe el flujo de alta publica de NoiaChat desde `/register`, la experiencia esperada para el usuario y los puntos tecnicos que deben mantenerse al ajustar el onboarding.

## Objetivo

Permitir que una persona cree una cuenta limitada, registre su empresa, registre una sede inicial y empiece con el plan basico de prueba sin intervencion manual del equipo interno.

## Flujo funcional

1. La persona entra desde `/login` usando la accion `Crear cuenta de prueba` o navega directamente a `/register`.
2. Completa los datos del responsable:
   - Nombre del responsable.
   - Correo de acceso.
3. Completa los datos de empresa:
   - Nombre comercial.
   - Razon social.
   - NIT / identificacion tributaria.
4. Completa los datos de sede inicial:
   - Nombre de la sede, por defecto `Principal`.
   - Ciudad.
5. Define y confirma la contrasena.
6. Al enviar el formulario, el sistema crea:
   - Usuario responsable.
   - Empresa.
   - Sede inicial.
   - Membresia activa con rol `company_admin`.
   - Suscripcion `trialing` al plan `basic_trial`.
7. El sistema inicia sesion y redirige al dashboard con el contexto de empresa/sede activo.

## Experiencia de interfaz

- `/login` muestra una accion visible para crear cuenta de prueba.
- `/register` usa una vista dedicada, no el layout generico de invitado.
- En escritorio, la vista separa informacion comercial/operativa del formulario.
- El formulario agrupa los campos en secciones:
  - Responsable.
  - Empresa.
  - Sede inicial.
  - Seguridad.
- Los campos `Contrasena` y `Confirmar contrasena` tienen boton de mostrar/ocultar.
- Las acciones principales son:
  - `Empezar prueba`.
  - `Ya tienes cuenta?`.

## Reglas de negocio

- El registro publico siempre crea una empresa nueva.
- El primer usuario queda como `company_admin` limitado a su propia empresa.
- La sede inicial queda asociada a la empresa creada.
- El trial se define desde la base de datos mediante el plan `basic_trial`; no debe depender de `.env` como fuente principal de negocio.
- Los roles no habilitan funcionalidades por si solos: las features y limites aplican segun el plan de la empresa.
- Si el trial vence, el usuario puede iniciar sesion, pero las acciones operativas quedan bloqueadas segun las reglas de billing.

## Validaciones y mensajes

- Los mensajes de validacion deben mostrarse en espanol.
- La configuracion base del proyecto usa `APP_LOCALE=es`.
- Existe compatibilidad temporal para `APP_LOCALE=en` mediante `lang/en/validation.php`, que reutiliza las traducciones en espanol. Esto evita mensajes en ingles si produccion aun conserva el locale anterior.
- Los nombres de atributos del registro estan traducidos para que los errores sean claros:
  - `password`: `contrasena`.
  - `password_confirmation`: `confirmacion de contrasena`.
  - `company_name`: `nombre comercial`.
  - `branch_name`: `nombre de la sede`.
- Caso esperado para contrasenas diferentes: `La confirmacion de contrasena no coincide.`

## Configuracion productiva

En `.env` de produccion debe quedar:

```env
APP_LOCALE=es
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=es_CO
```

Despues de cambiar variables de idioma o archivos de traduccion, ejecutar durante el despliegue:

```bash
php artisan config:cache
php artisan view:cache
```

## Archivos principales

- `routes/auth.php`: rutas `GET /register` y `POST /register`.
- `app/Http/Controllers/Auth/RegisteredUserController.php`: validacion y creacion transaccional del alta.
- `app/Services/Auth/TrialCompanyRegistration.php`: creacion de empresa, sede, membresia y trial.
- `resources/views/auth/login.blade.php`: acceso visible al registro.
- `resources/views/auth/register.blade.php`: vista de registro publico.
- `lang/es/validation.php`: mensajes de validacion en espanol.
- `lang/en/validation.php`: compatibilidad temporal para locale `en`.
- `tests/Feature/Auth/RegistrationTest.php`: pruebas del flujo y mensajes de validacion.

## Evidencia de pruebas

Pruebas recomendadas para validar este flujo:

```bash
php artisan test tests/Feature/Auth/RegistrationTest.php tests/Feature/Auth/AuthenticationTest.php
npm run build
```

Cobertura actual:

- Render de pantalla de registro.
- Creacion de usuario, empresa, sede, membresia y suscripcion trial.
- Validacion de empresa y sede obligatorias.
- Mensaje de validacion en espanol para confirmacion de contrasena.

## Checklist antes de publicar cambios de onboarding

- [ ] `/login` muestra la accion para crear cuenta de prueba.
- [ ] `/register` carga correctamente en desktop y movil.
- [ ] El formulario crea empresa, sede y trial.
- [ ] Los errores de validacion aparecen en espanol.
- [ ] Los botones de mostrar/ocultar contrasena funcionan.
- [ ] El usuario queda autenticado y redirigido al dashboard.
- [ ] Produccion tiene `APP_LOCALE=es`.
- [ ] Se ejecutaron pruebas de registro y autenticacion.
