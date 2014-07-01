import json
import requests
import unittest
import time

#url = "http://localhost:5000/url"
url = "http://10.0.0.3/~michal/con/con.php"

class TestContinuation(unittest.TestCase):

    def test_incorrect_post(self):
        r = requests.post(url)
        self.assertEqual(r.text, "Incorrect data")
        self.assertEqual(r.status_code, 400)

    def test_correct_post(self):
        payload = {'client': 'data'}
        r = requests.post(url, data=json.dumps(payload))
        self.assertEqual(r.status_code, 200)

    def test_incorrect_get(self):
        r = requests.get(url + "?text=bla")
        self.assertEqual(r.text, "Incorrect key")
        self.assertEqual(r.status_code, 403)

    def test_correct_get(self):
        payload = {'client': 'key=value', 'url':'/'}
        r = requests.post(url, data=json.dumps(payload))
        r = requests.get(url + json.loads(r.text), allow_redirects=False)
        self.assertEqual(r.status_code, 302)

    def test_long_url(self):
        redir_url = 'http://'+'long.url/'*1000
        payload = {'client': 'key=value', 'url': redir_url}
        r = requests.post(url, data=json.dumps(payload))
        r = requests.get(url + json.loads(r.text), allow_redirects=False)
        self.assertEqual(r.headers['location'], redir_url)
        self.assertEqual(r.status_code, 302)

    def test_incorrect_key_get(self):
        payload = {'client': 'key=value', 'url':'/'}
        r = requests.post(url, data=json.dumps(payload))
        postfix = json.loads(r.text)[1:]
        args = postfix.split('&')
        new_args = [ 'text=badmsg' if s.startswith('text=') else s \
            for s in args]
        new_postfix = '&'.join(new_args)
        r = requests.get(url + '?' + new_postfix, allow_redirects=False)
        self.assertEqual(r.status_code, 403)
        self.assertEqual(r.text, 'Incorrect key')

    def test_correct_cookies(self):
        payload = {'client': 'key=value', 'url':'/'}
        header = {'cookie': 'flavour=chocolate; session=eyJ1c2VybmFtZSI6Im1pcmphbSJ9.BnJ10A.VSuoI0CLPpDDbundvRuaP2v-9OM'}
        r = requests.post(url, data=json.dumps(payload), headers=header)
        r = requests.get(url+ json.loads(r.text), allow_redirects=False)
        self.assertEqual(r.status_code, 302)
        cookies = sorted([(cookie.name, cookie.value) for cookie in r.cookies])
        self.assertEqual(cookies, [('flavour', 'chocolate'), ('key', 'value'), ('session', 'eyJ1c2VybmFtZSI6Im1pcmphbSJ9.BnJ10A.VSuoI0CLPpDDbundvRuaP2v-9OM')])

    def test_delete_key(self):
        payload = {'client': 'key=value', 'url': '/some_url'}
        r = requests.post(url, data=json.dumps(payload))
        suffix = json.loads(r.text)
        self.assertEqual(r.status_code, 200)
        r = requests.get(url + suffix, allow_redirects=False)
        self.assertEqual(r.status_code, 302)
        r = requests.get(url + suffix, allow_redirects=False)
        self.assertEqual(r.status_code, 403)
        self.assertEqual(r.text, 'Incorrect key')

if __name__ == '__main__':
    unittest.main()
