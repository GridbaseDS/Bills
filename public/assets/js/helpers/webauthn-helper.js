/**
 * GridBase Bills — WebAuthn (Biometric Face ID / Touch ID / Fingerprint) Helper
 */

export const WebAuthnHelper = {
    // Utility: Convert Base64URL string to Uint8Array
    base64UrlToUint8Array(base64UrlString) {
        const padding = '='.repeat((4 - (base64UrlString.length % 4)) % 4);
        const base64 = (base64UrlString + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    },

    // Utility: Convert ArrayBuffer to Base64URL string
    arrayBufferToBase64Url(buffer) {
        const bytes = new Uint8Array(buffer);
        let binary = '';
        for (let i = 0; i < bytes.byteLength; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return window.btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
    },

    // Check if device supports Face ID / Touch ID / Passkeys
    async isSupported() {
        if (!window.PublicKeyCredential) return false;
        try {
            return await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
        } catch (e) {
            return false;
        }
    },

    // Register Biometric sensor (Face ID / Touch ID)
    async register(authenticatorName = 'Dispositivo Biométrico') {
        const supported = await this.isSupported();
        if (!supported) {
            throw new Error('Este navegador o dispositivo no soporta la autenticación biométrica (Face ID / Touch ID).');
        }

        let deviceToken = localStorage.getItem('device_token') || 'auto';

        // 1. Get Registration Options from Server
        const options = await window.App.api('auth/webauthn/register-options', { method: 'POST' });

        // Convert strings to Uint8Array
        options.challenge = this.base64UrlToUint8Array(options.challenge);
        options.user.id = this.base64UrlToUint8Array(options.user.id);

        if (options.excludeCredentials) {
            options.excludeCredentials = options.excludeCredentials.map(c => ({
                ...c,
                id: this.base64UrlToUint8Array(c.id)
            }));
        }

        // 2. Prompt Device Sensor (Face ID / Touch ID)
        const credential = await navigator.credentials.create({ publicKey: options });

        // 3. Send Credentials to Server
        const attestationObject = this.arrayBufferToBase64Url(credential.response.attestationObject);
        const clientDataJSON = this.arrayBufferToBase64Url(credential.response.clientDataJSON);

        const res = await window.App.api('auth/webauthn/register', {
            method: 'POST',
            body: {
                attestationObject,
                clientDataJSON,
                device_token: deviceToken,
                authenticator_name: authenticatorName
            }
        });

        if (res.device_token) {
            localStorage.setItem('device_token', res.device_token);
        }

        return res;
    },

    // Login with Biometric sensor (Face ID / Touch ID)
    async login(email) {
        const supported = await this.isSupported();
        if (!supported) {
            throw new Error('Este navegador o dispositivo no soporta la autenticación biométrica.');
        }

        const deviceToken = localStorage.getItem('device_token');
        if (!deviceToken) {
            throw new Error('Dispositivo no autorizado para inicio de sesión biométrico.');
        }

        // 1. Get Login Options from Server
        const options = await window.App.api('auth/webauthn/login-options', {
            method: 'POST',
            body: { email, device_token: deviceToken }
        });

        options.challenge = this.base64UrlToUint8Array(options.challenge);
        options.allowCredentials = options.allowCredentials.map(c => ({
            ...c,
            id: this.base64UrlToUint8Array(c.id)
        }));

        // 2. Prompt Device Sensor (Face ID / Touch ID)
        const credential = await navigator.credentials.get({ publicKey: options });

        // 3. Send Assertion to Server
        const authenticatorData = this.arrayBufferToBase64Url(credential.response.authenticatorData);
        const clientDataJSON = this.arrayBufferToBase64Url(credential.response.clientDataJSON);
        const signature = this.arrayBufferToBase64Url(credential.response.signature);

        const res = await window.App.api('auth/webauthn/login', {
            method: 'POST',
            body: {
                credential_id: credential.id,
                authenticatorData,
                clientDataJSON,
                signature,
                device_token: deviceToken
            }
        });

        return res;
    }
};
