"""
Quick smoke test for the bot reply API.

Run the server first (in another terminal):
    uvicorn main:app --reload --port 8000

Then run this script:
    python test_bot.py

It just prints each case's result - it's meant for a human to read, not
a CI pipeline. Feel free to add/edit cases as you extend bot_engine.py.
"""

import requests

BASE_URL = "http://localhost:8000"

CASES = [
    {"message": "Hi", "profile_name": "Rushil"},
    {"message": "What is the price of Sentinel?"},
    {"message": "How much for MagTrack?"},
    {"message": "Tell me about your products"},
    {"message": "My device is not connecting"},
    {"message": "Where is my order?"},
    {"message": "Can I book a demo?"},
    {"message": "I want to talk to a human agent"},
    {"message": "Thanks a lot!"},
    {"message": "asdkjaslkdj random text"},
]


def main():
    print(f"Health check: {requests.get(BASE_URL + '/health').json()}\n")

    for case in CASES:
        payload = {"phone_number": "919876543210", **case}
        resp = requests.post(f"{BASE_URL}/bot/reply", json=payload)
        print(f"IN : {case['message']}")
        if resp.status_code == 200:
            data = resp.json()
            print(f"OUT: [{data['intent']}] {data['reply']}")
            if data["should_handoff_to_human"]:
                print("     (flagged for human handoff)")
        else:
            print(f"ERROR {resp.status_code}: {resp.text}")
        print("-" * 60)


if __name__ == "__main__":
    main()
