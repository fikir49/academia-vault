const fs = require('fs');
const path = require('path');

// A secure, low-overhead structural masking key used to obfuscate raw file byte buffers
const OBFUSCATION_KEY = 0xAC; 

class SecurityEngine {
    /**
     * Obfuscates a raw file buffer using a bitwise XOR transformation mask
     * @param {Buffer} buffer - The pristine incoming file byte stream
     * @return {Buffer} - The structurally masked byte stream
     */
    static applyMask(buffer) {
        const maskedBuffer = Buffer.alloc(buffer.length);
        for (let i = 0; i < buffer.length; i++) {
            // Bitwise XOR flips the bits against our key, masking the true file signatures
            maskedBuffer[i] = buffer[i] ^ OBFUSCATION_KEY;
        }
        return maskedBuffer;
    }

    /**
     * Reverses the obfuscation mask to reconstruct the original file payload
     * @param {Buffer} maskedBuffer - The obfuscated byte stream from disk
     * @return {Buffer} - The pristine original file byte stream
     */
    static removeMask(maskedBuffer) {
        // XOR is completely symmetric; running it a second time perfectly restores the data
        return this.applyMask(maskedBuffer);
    }
}

module.exports = SecurityEngine;
