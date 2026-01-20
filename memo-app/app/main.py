from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
import psycopg
import os

app = FastAPI()

DB_URL = "postgresql://memo:memo123@127.0.0.1:5432/memoapp"

class NoteIn(BaseModel):
    title: str
    content: str | None = None

@app.get("/notes")
def list_notes():
    with psycopg.connect(DB_URL) as conn:
        with conn.cursor() as cur:
            cur.execute("SELECT id, title, content FROM notes ORDER BY id;")
            rows = cur.fetchall()
    return [{"id": r[0], "title": r[1], "content": r[2]} for r in rows]

@app.post("/notes")
def create_note(note:NoteIn):
    with psycopg.connect(DB_URL) as conn:
        with conn.cursor() as cur:
            cur.execute(
                "INSERT INTO notes (title, content) VALUES(%s,%s) RETURNING id;",
                (note.title, note.content),
            ) 
            new_id = cur.fetchone()[0]
            conn.commit()
    return {"id": new_id}

@app.get("/notes/{note_id}")
def get_note(note_id: int):
    with psycopg.connect(DB_URL) as conn:
        with conn.cursor() as cur:
            cur.execute(
                "SELECT id, title, content FROM notes WHERE id=%s;",
                (note_id,),
            )
            row = cur.fetchone()
            if not row:
                raise HTTPException(status_code=404, detail="Not found")
    return {"id": row[0], "title":row[1], "content": row[2]}

@app.delete("/notes/{note_id}")
def delete_note(note_id: int):
    with psycopg.connect(DB_URL) as conn:
        with conn.cursor() as cur:
            cur.execute(
                "DELETE FROM notes WHERE id=%s RETURNING id;",
                (note_id,),
            )
            row = cur.fetchone()
            if not row:
                raise HTTPException(status_code=404, detail="Not found")
            conn.commit()
    return {"deleted": note_id}
