
import os
import json
import time
import base64
import string
from Crypto import Random
from Crypto.Cipher import AES
from flask import Flask, request, redirect, make_response

app = Flask(__name__)
app.secret_key = '7rHEb1OIuNw3Un8GzES/2k6WVHbmM71BbpU6kjgfNqouxqetCIhRnQ=='

@app.route('/url', methods=['POST'])
def post_url():
    all_cookies = _get_cookies(request.environ.get('HTTP_COOKIE', ''))
    t = str(time.time())
    try:
        data = json.loads(request.data)
    except:
        return "Incorrect data", 400
    print data
    data['client'] = _get_cookies(data.get('client', ''))
    data['httponly'] = [ cookie for cookie in all_cookies if cookie not in data['client'] ]
    aes = AESCipher(app.secret_key+t)
    encrypted = aes.encrypt(json.dumps(data))
    decrypted = aes.decrypt(encrypted)
    assert decrypted == json.dumps(data)

    return json.dumps('?t=' + t + '&text=' + encrypted)

@app.route('/url', methods=['GET'])
def get_url():
    t = request.args.get('t')
    if (not t or (time.time() - float(t)) > 15):
        return "URL expired.", 403
    try:
        aes = AESCipher(app.secret_key+t)
        decrypted = aes.decrypt(str(request.args.get('text')))
        obj = json.loads(decrypted)
    except:
        return "Incorrect key", 403
    resp = make_response(redirect(obj['url']))
    for k,v in obj['httponly']:
        resp.set_cookie(k, v, httponly=True)
    for k,v in obj['client']:
        resp.set_cookie(k, v)
    return resp


class AESCipher:
    def __init__(self, key):
        self.bs = 32
        if len(key) >= 32:
            self.key = key[:32]
        else:
            self.key = self._pad(key)

    def encrypt(self, raw):
        raw = self._pad(raw)
        iv = Random.new().read(AES.block_size)
        cipher = AES.new(self.key, AES.MODE_CBC, iv)
        return base64.urlsafe_b64encode(iv + cipher.encrypt(raw))

    def decrypt(self, enc):
        enc = base64.urlsafe_b64decode(enc)
        iv = enc[:AES.block_size]
        cipher = AES.new(self.key, AES.MODE_CBC, iv)
        return self._unpad(cipher.decrypt(enc[AES.block_size:]))

    def _pad(self, s):
        return s + (self.bs - len(s) % self.bs) * chr(self.bs - len(s) % self.bs)

    def _unpad(self, s):
        return s[:-ord(s[len(s)-1:])]


# DO NOT RELY ON BUILTIN COOKIE HANDLING!
# The builtin cookie handling can be buggy as it tries to cover too
# many cases, we are just interested in the basics so the approach below
# is more robust.
def _get_cookies(cookiestr):
    cookies = []

    for cookie in cookiestr.split(";"):
        key, value = _get_key_value(cookie)
        cookies.append((key, value))
    cookies.sort()
    return cookies

def _get_key_value(cookiestr):
    try:
        key, value = cookiestr.split('=', 1)
    except ValueError:
        return '', cookiestr.strip()
    return key.strip(), value



if __name__ == "__main__":
    app.debug= True
    app.run()
