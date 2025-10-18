Análisis del Flujo Real
Usuario accede a PublicShow.vue: El PublicSurveyController@show carga los detalles de la encuesta y el estado del usuario (userProgress).
VoteInterface.vue se monta: Necesita saber cuál es la próxima pareja de personajes para votar.
VoteInterface.vue solicita la pareja: Debe hacer una llamada a un nuevo endpoint, por ejemplo, GET /api/surveys/{survey}/next-combination.
Backend procesa la solicitud:
Valida que el usuario esté autenticado y tenga permiso para votar en esa encuesta (activo, no completada).
Usa el CombinatoricService->getNextCombination($survey, $user->id) para obtener la próxima combinación.
Devuelve la combinación (o null si no hay más) en formato JSON.
VoteInterface.vue recibe la respuesta: Actualiza su estado currentCombination con los datos recibidos.
Usuario vota: El voto se envía a SurveyVoteController@store.
SurveyVoteController@store procesa el voto: (Ya lo tenemos optimizado).
Opción A (Recomendada): SurveyVoteController@store devuelve también la próxima combinación posible tras procesar el voto exitosamente. VoteInterface.vue recibe esta nueva combinación en la respuesta de onSuccess de voteForm.post y actualiza su estado inmediatamente, sin necesidad de una nueva solicitud.
Opción B (Alternativa): SurveyVoteController@store solo confirma el voto. VoteInterface.vue luego vuelve a llamar al endpoint GET /api/surveys/{survey}/next-combination para obtener la siguiente pareja.
La Opción A es más eficiente, ya que minimiza las solicitudes al backend. La Opción B es más sencilla de implementar si no se quiere modificar SurveyVoteController@store, pero implica una solicitud extra por voto.

Sugerencia: Implementar la Opción A (Endpoint + Modificación de SurveyVoteController)
Crear el Endpoint: GET /api/surveys/{survey}/next-combination manejado por un nuevo método en un controlador (por ejemplo, Api/SurveyController o un método en PublicSurveyController).
Actualizar VoteInterface.vue: Modificarlo para llamar a este nuevo endpoint al montarse o cuando sea necesario reiniciar la carga de combinaciones. Manejar correctamente la respuesta (cargando la combinación o indicando que no hay más).
Modificar SurveyVoteController@store: Añadir la lógica para obtener y devolver la próxima combinación junto con el mensaje de éxito del voto. Asegurarse de que esta lógica se ejecute después de que se haya procesado y confirmado el voto actual, pero antes de finalizar la transacción si es necesario (aunque markCombinationUsed ya la marca como usada, por lo que getNextCombination debería devolver una diferente o null).
Actualizar VoteInterface.vue: Modificar la lógica onSuccess de voteForm.post para que, en lugar de llamar a loadNextCombination(), use la combinación devuelta en la respuesta del backend.
