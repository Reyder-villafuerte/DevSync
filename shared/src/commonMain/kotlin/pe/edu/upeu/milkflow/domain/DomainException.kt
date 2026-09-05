package pe.edu.upeu.milkflow.domain

sealed class DomainException(message: String) : Exception(message)

class ProductorNoEncontradoException(productorId: String) :
    DomainException("No existe el productor con identificador '$productorId'.")

class ProductorInactivoException(productorId: String) :
    DomainException("El productor '$productorId' no está activo.")

class AcopiadorNoEncontradoException(acopiadorId: String) :
    DomainException("No existe el acopiador con identificador '$acopiadorId'.")

class EntregaNoEncontradaException(entregaId: String) :
    DomainException("No existe la entrega con identificador '$entregaId'.")

class UsuarioNoEncontradoException(usuarioId: String) :
    DomainException("No existe el usuario con identificador '$usuarioId'.")

class CredencialesInvalidasException : DomainException("Las credenciales son inválidas.")

class CuentaPendienteException :
    DomainException("Tu cuenta está pendiente de asignación de rol por la administradora.")

class UsuarioInactivoException(usuarioId: String) :
    DomainException("El usuario '$usuarioId' no está activo.")

class LitrosInvalidosException(litros: Double) :
    DomainException("Los litros deben ser un número finito mayor que cero. Valor recibido: $litros.")

class EntregaInvalidaException(motivo: String) : DomainException(motivo)

class PruebaCalidadInvalidaException(motivo: String) : DomainException(motivo)

class RangoFechasInvalidoException :
    DomainException("El inicio del rango debe ser anterior a su fin exclusivo.")

class AccesoDenegadoException(
    rol: String,
    accion: String,
) : DomainException("El rol '$rol' no tiene permiso para ejecutar '$accion'.")
