from fastapi import FastAPI, HTTPException, Query
from pydantic import BaseModel
import mysql.connector
import os

APP_INFO = {
    "name": "SMSDv3 API",
    "version": "1.0.0-alpha"
}

app = FastAPI(title="SMSDv3 API", version=APP_INFO['version'])

# config db
DB_CONFIG = {
    "host": os.getenv("DB_HOST"),
    "user": os.getenv("DB_USER"),
    "password": os.getenv("DB_PASS"),
    "database": os.getenv("DB_NAME")
}

def check_api_key(api_key: str):
    """
    Check if an API key is valid.

    Args:
        api_key (str): The API key to check.

    Returns:
        bool: True if the API key is valid, False otherwise.

    Raises:
        HTTPException: If the API key is empty or invalid, or if there is a database connection error.
    """
    if not api_key or api_key.strip() == "":
        raise HTTPException(
            status_code=400,
            detail="API key is required"
        )
        
    try:
        cnx = mysql.connector.connect(**DB_CONFIG)
        
        cursor = cnx.cursor()
        cursor.execute("SELECT username FROM users WHERE api_key = %s", (api_key,))
        
        result = cursor.fetchone()
        
        if result:
            return True
        else:
            return False
        
        cursor.close()
        cnx.close()
    except mysql.connector.Error as err:
        raise HTTPException(
            status_code=500,
            detail="Database connection error"
        )

def get_db_connection():
    """
    Return a connection to the database using the configuration defined in DB_CONFIG.

    Returns:
        mysql.connector.MySQLConnection: A connection to the database.
    """
    return mysql.connector.connect(**DB_CONFIG)

# ? API

@app.get("/")
async def root():
    """
    Return a JSON response with the status, message and version of the SMSDv3 API.

    Returns:
        dict: A JSON response with the status, message and version of the SMSDv3 API.
    """
    return {
        "status": "success",
        "message": APP_INFO['name']+" is running on version "+APP_INFO['version'],
        "version": APP_INFO['version']
    }
    
@app.get("/api/test")
async def api_test_key(api_key: str):
    """
    Test if an API key is valid.

    Args:
        api_key (str): The API key to test.

    Returns:
        dict: A JSON response with the status and message of the API key test.

    Raises:
        HTTPException: If the API key is empty or invalid, or if there is a database connection error.
    """
    if not api_key or api_key.strip() == "":
        raise HTTPException(
            status_code=400,
            detail="API key is required"
        )
        
    try:
        cnx = mysql.connector.connect(**DB_CONFIG)
        
        cursor = cnx.cursor()
        cursor.execute("FROM users WHERE api_key = %s", (api_key,))
        
        result = cursor.fetchone()
        
        if result:
            return {
                "status": "success",
                "message": "API key is valid ! Welcome "+result[0]
            }
        else:
            return {
                "status": "error",
                "message": "API key is invalid"
            }
        
        cursor.close()
        cnx.close()
    except mysql.connector.Error as err:
        raise HTTPException(
            status_code=500,
            detail="Database connection error"
        )
        
@app.get("/users/{username}")
async def get_user(api_key: str, username: str):
    if not check_api_key(api_key):
        raise HTTPException(
            status_code=400,
            detail="API key is invalid"
        )
        
    try:
        cnx = mysql.connector.connect(**DB_CONFIG)
        
        cursor = cnx.cursor()
        cursor.execute("SELECT username, picture, created_at, last_login, is_active FROM users WHERE username = %s", (username,))
        
        result = cursor.fetchone()
        
        if result:
            return {
                "status": "success",
                "message": "User found",
                "data": {
                    "username": result[0],
                    "picture": result[1],
                    "picture_http": "https://raw.githubusercontent.com/kerogs/sword-master-story-dashboard-3/refs/heads/main/"+result[1],
                    "created_at": result[2],
                    "last_login": result[3],
                    "is_active": result[4]
                }
            }
        else:
            return {
                "status": "error",
                "message": "User not found"
            }
        
        cursor.close()
        cnx.close()
    
    except mysql.connector.Error as err:
        raise HTTPException(
            status_code=500,
            detail="Database connection error"
        )
        
@app.get("/users")
async def get_all_user(api_key: str):
    if not check_api_key(api_key):
        raise HTTPException(
            status_code=400,
            detail="API key is invalid"
        )
        
    try:
        cnx = mysql.connector.connect(**DB_CONFIG)
        
        cursor = cnx.cursor()
        cursor.execute("SELECT username, picture, created_at, last_login, is_active FROM users")
        
        result = cursor.fetchall()
        
        resultAlt = []
        
        for result in result:
            resultAlt.append({
                "username": result[0],
                "picture": result[1],
                "picture_http": "https://raw.githubusercontent.com/kerogs/sword-master-story-dashboard-3/refs/heads/main/"+result[1],
                "created_at": result[2],
                "last_login": result[3],
                "is_active": result[4]
            })
        
        if result:
            return {
                "status": "success",
                "message": "Users found",
                "data": resultAlt
            }
        else:
            return {
                "status": "error",
                "message": "Users not found"
            }
        
        cursor.close()
        cnx.close()
    
    except mysql.connector.Error as err:
        raise HTTPException(
            status_code=500,
            detail="Database connection error"
        )