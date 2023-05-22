""" From model.py
class K6ScdContent(models.Model):
    asset_id = models.PositiveIntegerField()
    title = models.CharField(max_length=255)
    alias = models.CharField(max_length=400, db_collation='utf8mb4_bin')
    introtext = models.TextField()
    fulltext = models.TextField()
    state = models.IntegerField()
    catid = models.PositiveIntegerField()
    created = models.DateTimeField()
    created_by = models.PositiveIntegerField()
    created_by_alias = models.CharField(max_length=255)
    modified = models.DateTimeField()
    modified_by = models.PositiveIntegerField()
    checked_out = models.PositiveIntegerField()
    checked_out_time = models.DateTimeField()
    publish_up = models.DateTimeField()
    publish_down = models.DateTimeField()
    images = models.TextField()
    urls = models.TextField()
    attribs = models.CharField(max_length=5120)
    version = models.PositiveIntegerField()
    ordering = models.IntegerField()
    metakey = models.TextField()
    metadesc = models.TextField()
    access = models.PositiveIntegerField()
    hits = models.PositiveIntegerField()
    metadata = models.TextField()
    featured = models.PositiveIntegerField()
    language = models.CharField(max_length=7)
    xreference = models.CharField(max_length=50)
    note = models.CharField(max_length=255)

    class Meta:
        managed = False
        db_table = 'prefix_content'
"""

from django.http import JsonResponse,HttpResponse
from .models import K6ScdContent

def get_articules_by_categorys(ids):
    try:
        response=K6ScdContent.objects.filter(catid__in=ids).values("id","title")
    except ValueError as ve:
        response={"Error":"Error, la entrada '{}' es incorrecta; Error:{}".format(ids,ve)}
    return {"response":response}

def HomeView(request):    
    response=get_articules_by_categorys(range(0,100))
    if response.get("Error"):
        return HttpResponse(response["Error"])
    return JsonResponse(list(response["response"]),safe=False)