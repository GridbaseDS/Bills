import sys
from cryptography.hazmat.primitives.serialization import pkcs12
from cryptography.hazmat.primitives import serialization

password = b'SamDP9903'
with open('storage/app/secure/certificado.p12', 'rb') as f:
    p12_data = f.read()

try:
    private_key, certificate, additional_certificates = pkcs12.load_key_and_certificates(
        p12_data, password
    )

    # Modern encryption
    encryption = serialization.BestAvailableEncryption(password)
    
    modern_p12 = pkcs12.serialize_key_and_certificates(
        b"DGII Certificate",
        private_key,
        certificate,
        additional_certificates,
        encryption
    )

    with open('storage/app/secure/certificado_moderno.p12', 'wb') as f:
        f.write(modern_p12)

    print("SUCCESS")
except Exception as e:
    print("ERROR:", str(e))
