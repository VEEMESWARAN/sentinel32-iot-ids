import itertools

def decrypt_vigenere(cipher, key):
    key = key.upper()
    result = []
    key_pos = 0

    for c in cipher:
        if c.isalpha():
            shift = ord(key[key_pos % len(key)]) - ord('A')
            plain = chr((ord(c.upper()) - ord('A') - shift) % 26 + ord('A'))
            result.append(plain)
            key_pos += 1
        else:
            result.append(c)

    return ''.join(result)

cipher = "NGMNIGCEZVIRYHCMVWZXTSKCXIPOGGZLCBAGDLMEXRRIIOC"

keys = [
    "LENOVO","DELL","ACER","ASUS","HP","THINKPAD","MSI",
    "ROG","ALIENWARE","FUJITSU","TOSHIBA",
    "HUAWEI","SAMSUNG","SURFACE","MICROSOFT"
]

for key in keys:
    print(f"\n[{key}]")
    print(decrypt_vigenere(cipher, key))