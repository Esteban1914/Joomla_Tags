"""
    /* ====== v1.0.0 ====== /*
"""
from django.http import JsonResponse,HttpResponse
from django.db.utils import OperationalError

""" From model.py
class Joomla_Content(models.Model):
    title = models.CharField(max_length=255)
    catid = models.PositiveIntegerField()
    class Meta:
        managed = False
class Joomla_Categories(models.Model):
     title = models.CharField(max_length=255)
     class Meta:
         managed = False
"""
from .models import Joomla_Content,Joomla_Categories

#!!!!   This improt need the excat address from settings.py !!!!
from joomla.settings import DATABASES

#Exception 
class Connection_DB_Error(Exception):
    pass

def get_articles_by_categories(host:str,port:str,name:str,user:str,passw:str,prefix:str,categorys_title_list:list)->dict | str:
    try:
        #Update Database server info
        DATABASES.update(
            {
            'db_Joomla_mysql':
                {
                    'ENGINE': 'django.db.backends.mysql',
                    'HOST': host,
                    'PORT': port,
                    'NAME': name,
                    'USER': user,
                    'PASSWORD': passw,
                    'ATOMIC_REQUESTS': False,
                    'AUTOCOMMIT': True,
                    'CONN_MAX_AGE': 0,
                    'CONN_HEALTH_CHECKS': False, 
                    'OPTIONS': {},
                    'TIME_ZONE': None,
                    'TEST': {
                        'CHARSET': None,
                        'COLLATION': None,
                        'MIGRATE': True,
                        'MIRROR': None,
                        'NAME': None
                        },
                }
            }
        )
        #Update prefix  databases table
        Joomla_Content._meta.db_table=prefix + "content"
        Joomla_Categories._meta.db_table=prefix + "categories"
        #Get Ids Categories from DB
        ids_list= Joomla_Categories.objects.using("db_Joomla_mysql").filter(title__in=categorys_title_list).values_list("id",flat=True)
        if ids_list:
            #Get Info Content from DB
            articles=Joomla_Content.objects.using("db_Joomla_mysql").filter(catid__in=ids_list).values("id","title")
            #Verify conections errors 
            if articles:
                return articles
        error="Cuidado!, no existen categorías para las contenidos dados"    
    except ValueError as ve:
        error="Error, la lista de entrada tiene un formato incorrecto; Error:{}".format(ve)
    except OperationalError as oe:
        error="Error, no se ha podido conectar a la base de datos; Error:{}".format(oe)
    except Exception as e:
        error="Error desconocido; Error:{}".format(e)
    raise Connection_DB_Error(error)
        
        
"""Prueba de la funcion 'get_articles_by_categories' desde Django """
def HomeView(request):
    names=["Category1","Category2","Category3"]
    try:
        response=get_articles_by_categories(
            host="127.0.0.1",
            port="3306",
            name="mybd",
            user="root",
            passw="",
            prefix="k6scd_",
            categorys_title_list=names,
            )
        return JsonResponse(list(response),safe=False)
   
    except Connection_DB_Error as cdb_error:
        print(cdb_error)
        return HttpResponse(cdb_error)
    