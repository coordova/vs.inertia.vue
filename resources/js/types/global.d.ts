// resources/js/types/global.d.ts

// Importaciones (si se necesitan para otros tipos, pero no para extender PageProps aquí)
// import { ... } from '...';

// Extender la interfaz PageProps de Inertia para incluir nuestras propiedades globales
declare module '@inertiajs/core' {
    /* interface PageProps {
        // Añadir aquí propiedades que *siempre* estarán presentes en todas las páginas
        // Por ejemplo, si defines auth globalmente en HandleInertiaRequests:
        // auth?: {
        //     user: { id: number; name: string; email: string } | null;
        // };

        // Añadir aquí propiedades que *podrían* estar presentes en *algunas* páginas
        // Nota: Esto las hace *opcionales* en todas las páginas, lo cual puede no ser ideal
        // para props específicas de una página. Se prefiere usar `defineProps` en el componente.
        // flash?: { success?: string; error?: string }; // Ejemplo de flash global
    } */
}

// Definir interfaces para las entidades que se pasan como props específicas
// Estas se usarán en defineProps de los componentes Vue
interface Pagination {
    current_page: number;
    from: number;
    last_page: number;
    path: string;
    per_page: number;
    to: number;
    total: number;
}

interface PaginationLinks {
    url: string | null;
    label: string;
    active: boolean;
}

export interface UserResource { // Interfaz para el recurso individual resuelto
    id: number;
    name: string;
    email: string;
    avatar: string;
    timezone: string;
    locale: string;
    type: string;
    status: boolean;
    last_login_at: string;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    created_at_formatted: string;
    updated_at_formatted: string;
    // Añadir otros campos devueltos por UserResource si es necesario
}

export interface UsersData { // Para la colección paginada
    data: UserResource[]; // Array de UserResource
    meta: Pagination;
    links: PaginationLinks[]; // Simplificado
}

export interface CategoryResource { // Renombrado para claridad
    id: number;
    name: string;
    description: string;
    image: string | null;
    status: boolean;
    is_featured: boolean;
    slug: string;
    created_at_formatted: string; // O created_at si se formatea en el frontend
    updated_at_formatted: string; // O updated_at si se formatea en el frontend
    // Añadir otros campos devueltos por CategoryResource
    surveys_count: number;
}

export interface CategoriesData { // Para la colección paginada
    data: CategoryResource[]; // Array de CategoryResource
    meta: Pagination;
    links: PaginationLinks[]; // Simplificado
}

// --- Interfaces para otras entidades ---
// --- Interfaces para Characters ---
export interface CharacterResource { // Interfaz para el recurso individual resuelto
    id: number;
    fullname: string;
    nickname: string;
    slug: string;
    bio: string;
    dob: string | null; // Puede ser null si no tiene fecha de nacimiento
    dob_for_humans: string | null; // Puede ser null si no tiene fecha de nacimiento
    dob_formatted: string | null; // Puede ser null si no tiene fecha de nacimiento
    gender: number | null; // 0=otro, 1=masculino, 2=femenino, 3=no-binario
    nationality: string;
    occupation: string;
    picture: File | null; // URL o path
    picture_url: string | undefined;
    thumbnail_url: string;
    status: boolean;
    meta_title: string | null;
    meta_description: string | null;
    created_at: string; // Formato ISO
    updated_at: string; // Formato ISO
    // Añadir otros campos devueltos por CharacterResource si es necesario
    created_at_formatted: string;
    updated_at_formatted: string;
    category_ids: number[];
}

export interface CharacterResourceForm { // Interfaz para el recurso individual resuelto
    id: number;
    fullname: string;
    nickname: string;
    slug: string;
    bio: string;
    dob: string | null; // Puede ser null si no tiene fecha de nacimiento
    gender: number | null; // 0=otro, 1=masculino, 2=femenino, 3=no-binario
    nationality: string;
    occupation: string;
    picture: File | null; // URL o path
    picture_url: string | undefined;
    status: boolean;
    meta_title: string | null;
    meta_description: string | null;
    // Añadir otros campos devueltos por CharacterResource si es necesario
    category_ids: number[];
}



export interface CharactersData { // Para la colección paginada resuelta
    data: CharacterResource[]; // Array de objetos CharacterResource directos
    meta: Pagination;
    links: PaginationLinks[];
}


// --- Interfaces para Surveys ---
export interface SurveyResource { // Interfaz para el recurso individual resuelto
    id: number;
    category_id: number; // ID de la categoría
    category: CategoryData; // Objeto de la categoría relacionada (resuelto)
    category: CategoryResource;
    title: string;
    slug: string;
    description: string;
    image: string | null; // URL o path
    type: number; // 0=pública, 1=privada
    status: boolean;
    reverse: boolean; // 0 : orden 'cual es mejor' (default) | 1 : orden 'cual es peor'
    date_start: string; // Formato ISO
    date_end: string; // Formato ISO
    selection_strategy: string; // Nombre de la estrategia
    max_votes_per_user: number | null; // 0=ilimitado
    allow_ties: boolean;
    tie_weight: number; // Decimal
    is_featured: boolean;
    is_active: boolean;
    sort_order: number;
    counter: number;
    meta_title: string | null;
    meta_description: string | null;
    created_at: string; // Formato ISO
    updated_at: string; // Formato ISO
    // deleted_at: string | null; // Incluido si se maneja soft delete y se envía

    date_start_formatted: string;
    date_end_formatted: string;
    duration: number;
    /* selection_strategy_info: {
        name: string;
        description: string;
        metadata: {
            [key: string]: any;
        };
    }; */
    character_count: number;
    combinations_count: number;
    combinatorics_count: number;
    user_votes_count: number;
    progress_percentage: number;
    is_completed: boolean;
    created_at_formatted: string;
    updated_at_formatted: string;
    characters: CharacterResource[];
    strategy: string;
    // votes: VoteResource[];

    total_combinations_expected: number;
    total_combinations: number;
    total_votes: number;
}

export interface SurveyResourceForm { // Interfaz para el recurso individual resuelto
    // id: number;
    category_id: string | number | null; // ID de la categoría
    // category: CategoryResource; // Objeto de la categoría relacionada (resuelto)
    title: string;
    // slug: string;
    description: string;
    // image: string | null; // URL o path
    type: number; // 0=pública, 1=privada
    status: boolean;
    reverse: boolean; // 0 : orden 'cual es mejor' (default) | 1 : orden 'cual es peor'
    date_start: string; // Formato ISO
    date_end: string; // Formato ISO
    selection_strategy: string; // Nombre de la estrategia
    // max_votes_per_user: number | null; // 0=ilimitado
    // allow_ties: boolean;
    // tie_weight: number; // Decimal
    is_featured: boolean;
    is_active: boolean;
    // sort_order: number;
    // counter: number;
    // meta_title: string | null;
    // meta_description: string | null;
    // created_at: string; // Formato ISO
    // updated_at: string; // Formato ISO
    // deleted_at: string | null; // Incluido si se maneja soft delete y se envía
    characters: number[];
}

export interface SurveysData {
    data: SurveyResource[];
    meta: Pagination;
    links: PaginationLinks[];
}

/*--------------------------------------------------------------------------*/
// --- Interfaces para el Progreso del Usuario en una Encuesta ---
export interface UserSurveyProgress {
    exists: boolean;
    is_completed: boolean;
    progress: number; // Porcentaje de progreso
    total_votes: number;
    total_expected: number | null; // Total de combinaciones esperadas
    pivot: SurveyUser | null; // El objeto pivote completo, si es necesario
}

// Asumiendo que SurveyUser es la estructura del modelo pivote
export interface SurveyUser {
    user_id: number;
    survey_id: number;
    progress_percentage: number;
    total_votes: number;
    total_combinations_expected: number | null; // Campo añadido
    completed_at: string | null;
    started_at: string;
    last_activity_at: string;
    is_completed: boolean;
    completion_time: number | null;
}

export interface CombinatoricResource_ {
    id: number;
    survey_id: number;
    character1_id: number;
    character2_id: number;
    character1: CharacterResource;
    character2: CharacterResource;
    created_at: string;
    updated_at: string;
}

export interface CombinatoricResource {
    id: number;
    character1: CharacterResource;
    character2: CharacterResource;
    // Añadir otros campos si son necesarios
}
/*--------------------------------------------------------------------------*/
// --- Interfaces para Rankings ---
// Interfaz para un registro de ranking de personaje en una categoría (fila de la tabla)
// Esta interfaz representa un objeto de la tabla pivote 'category_character' + la relación 'character' (resuelta como objeto plano)
// Asumiendo que el objeto que llega es la serialización directa de CategoryCharacter::with('character')->first()
export interface CategoryCharacterRankingResource {
    // Campos de la tabla pivote 'category_character'
    category_id: number;
    character_id: number;
    elo_rating: number;
    matches_played: number;
    wins: number;
    losses: number;
    ties: number; // Nueva columna
    win_rate: number | float; // Porcentaje
    highest_rating: number;
    lowest_rating: number;
    rating_deviation: number; // Si se usa Glicko
    last_match_at: string | null; // Formato ISO
    is_featured: boolean;
    sort_order: number;
    status: boolean;
    created_at: string; // Formato ISO
    updated_at: string; // Formato ISO
    // deleted_at: string | null; // Si se maneja soft delete y se envía

    // Relación con el modelo 'Character' (cargada como objeto plano, no como CharacterResource)
    // Asumiendo que el objeto 'character' contiene los campos devueltos por el modelo Character o su toArray si se usa
    character: {
        id: number;
        fullname: string;
        nickname: string | null;
        slug: string;
        bio: string | null;
        dob: string | null; // Formato ISO
        gender: number; // 0=otro, 1=masculino, 2=femenino, 3=no-binario
        nationality: string | null;
        occupation: string | null;
        picture: string | null; // Ruta relativa, como se define en el modelo
        status: boolean;
        meta_title: string | null;
        meta_description: string | null;
        created_at: string; // Formato ISO
        updated_at: string; // Formato ISO
        // deleted_at: string | null; // Si se maneja soft delete
    };

    // Campo calculado: posición en el ranking (añadido por el servicio RankingService)
    position?: number; // Campo opcional calculado
}

// --- CORRECCIÓN: Interfaz para la respuesta paginada de rankings ---
// La estructura que Inertia devuelve para un objeto Paginator de Laravel es un objeto plano
// con los campos de paginación en el nivel raíz, NO anidados en 'meta'.
export interface CategoryRankingData {
    data: CategoryCharacterRankingResource[]; // Array de entradas de ranking
    // Campos de paginación en el nivel raíz (como los devuelve Inertia de un Paginator)
    current_page: number;
    from: number;
    last_page: number;
    path: string;
    per_page: number;
    to: number;
    total: number;
    first_page_url: string | null;
    last_page_url: string | null;
    next_page_url: string | null;
    prev_page_url: string | null;
    links: { url: string | null; label: string; active: boolean }[]; // Links de paginación
    // No hay propiedad 'meta'
}

// Interfaz para un registro de ranking de personaje en una encuesta específica (fila de la tabla)
// Esta interfaz representa un objeto de la tabla pivote 'character_survey' + la relación 'character'(resuelta como objeto plano)
// Incluye la posición calculada por el servicio RankingService
export interface CharacterSurveyRankingResource {
    // Campos de la tabla pivote 'character_survey'
    character_id: number;
    survey_id: number;
    survey_matches: number;
    survey_wins: number;
    survey_losses: number;
    survey_ties: number; // Nueva columna
    is_active: boolean;
    sort_order: number;
    pivot_created_at: string; // Formato ISO
    pivot_updated_at: string; // Formato ISO
    // deleted_at: string | null; // Si se maneja soft delete y se envía

    // Campo calculado: posición en el ranking de la encuesta (añadido por el servicio RankingService)
    survey_position: number; // <-- Campo calculado y añadido por RankingService

    // Campos del rating ELO en la categoría de la encuesta (desde category_character)
    elo_rating_in_category: number; // <-- Campo del rating ELO
    matches_played_in_category: number; // Opcional
    wins_in_category: number;         // Opcional
    losses_in_category: number;       // Opcional
    ties_in_category: number;         // Opcional
    win_rate_in_category: number;     // Opcional

    // Relación con el modelo 'Character' (datos del personaje, incluyendo picture_url)
    character: {
        id: number;
        fullname: string;
        nickname: string | null;
        picture: string | null; // Ruta relativa
        picture_url: string | null; // URL generada por Storage::url
        slug: string;
        // Añadir otros campos necesarios del personaje si se usan en la UI
    };

    // Relación con el modelo 'Character' (cargada como objeto plano o CharacterResource)
    // Asumiendo que CharacterSurveyResource incluye 'character' como un objeto con campos específicos
    /* character: {
        id: number;
        fullname: string;
        nickname: string | null;
        slug: string;
        bio: string | null;
        dob: string | null; // Formato ISO
        gender: number; // 0=otro, 1=masculino, 2=femenino, 3=no-binario
        nationality: string | null;
        occupation: string | null;
        picture: string | null; // Ruta relativa
        picture_url: string | null; // URL generada por Storage::url (si se incluye en CharacterSurveyResource)
        status: boolean;
        meta_title: string | null;
        meta_description: string | null;
        created_at: string; // Formato ISO
        updated_at: string; // Formato ISO
        // deleted_at: string | null; // Si se maneja
    }; */
    // character: CharacterResource; // Incluye fullname, picture_url, etc.
}

// Interfaz para la respuesta paginada de rankings de encuesta
// La estructura que Inertia devuelve para una colección paginada de CharacterSurveyResource
export interface SurveyResultsData {
    data: CharacterSurveyRankingResource[]; // Array de entradas de ranking
    meta: {
        current_page: number;
        from: number;
        last_page: number;
        path: string;
        per_page: number;
        to: number;
        total: number;
    };
    links: { url: string | null; label: string; active: boolean }[]; // Links de paginación
}

// --- Interfaces para Characters ---

// Interfaz para un registro de relación personaje-categoría (tabla pivote category_character)
// Devuelto por CategoryCharacterResource
export interface CategoryCharacterStatResource {
    // Campos del pivote 'category_character' (accedidos a través de $this->resource->pivot)
    character_id: number;
    category_id: number;
    elo_rating: number; // Asegurar tipo decimal si es necesario
    matches_played: number;
    wins: number;
    losses: number;
    ties: number; // Nueva columna
    win_rate: number; // Porcentaje
    highest_rating: number;
    lowest_rating: number;
    rating_deviation: number; // Si se usa Glicko
    last_match_at: string | null; // Formato ISO
    is_featured: boolean;
    sort_order: number;
    status: boolean;
    pivot_created_at: string; // Formato ISO (created_at de category_character)
    pivot_updated_at: string; // Formato ISO (updated_at de category_character)

    // Información resumida de la categoría (devuelta por CategoryCharacterResource)
    category_info: {
        id: number;
        name: string;
        slug: string;
        color: string; // Hex color
        icon: string; // Icon name or URL
    };
    // Opcional: Si se devuelve la categoría completa como objeto anidado
    // category: CategoryResource;
}

// Interfaz para un registro de relación personaje-encuesta (tabla pivote character_survey)
// Devuelto por CharacterSurveyResource
export interface CharacterSurveyParticipationResource {
    // Campos del pivote 'character_survey' (accedidos a través de $this->resource->pivot)
    character_id: number;
    survey_id: number;
    survey_matches: number;
    survey_wins: number;
    survey_losses: number;
    survey_ties: number; // Nueva columna
    is_active: boolean;
    sort_order: number;
    pivot_created_at: string; // Formato ISO (created_at de character_survey)
    pivot_updated_at: string; // Formato ISO (updated_at de character_survey)

    // Campo calculado: posición en el ranking de la encuesta (añadido por el servicio RankingService o calculado aquí si se implementa)
    // survey_position?: number; // Incluir si se implementa

    // Relación con la encuesta (devuelta por CharacterSurveyResource como objeto resuelto)
    survey: SurveyResource; // <-- Debe ser directamente SurveyResource, no {  SurveyResource }
}

// Interfaz para el recurso de estadísticas del personaje (detalle de personaje + estadísticas)
// Extiende de CharacterResource para reutilizar campos básicos
export interface CharacterStatsResource extends CharacterResource { // Extiende de CharacterResource
    // Añadir relaciones específicas para estadísticas (ya resueltas)
    categories_stats: CategoryCharacterStatResource[]; // <-- Array directo de objetos con datos pivote y category_info
    surveys_participation: CharacterSurveyParticipationResource[]; // <-- Array directo de objetos con datos pivote y survey
}

// Interfaz para la respuesta de resultados de encuesta
/* export interface SurveyResultsData {
    survey: SurveyResource; // Datos de la encuesta
    ranking: CharacterSurveyRankingResource[]; // Ranking de personajes en la encuesta
    // Puedes añadir otras estadísticas generales de la encuesta aquí si se pasan
    // general_stats?: { total_votes: number, total_participants: number, ... }
} */
/*--------------------------------------------------------------------------*/
// Asegurarse de que SurveyResource y CharacterResource estén definidos o importados si se usan aquí
// export interface SurveyResource { ... }
// export interface CharacterResource { ... }

/*--------------------------------------------------------------------------*/
// --- Tipos para Breadcrumbs (si no los tienes en otro lugar) ---
/* export interface BreadcrumbItem {
    title: string;
    href?: string;
} */