<?php

/**
 * @OA\Info(title="CourseWay Chamilo API", version="0.1")
 * @OA\Server(url="/plugin/courseway/api/v1")
 * @OA\Swagger(
 *  schemes={"http", "https"}
 * )
 * @OA\SecurityScheme(
 *  type="http",
 *  description="Use /auth to get token.",
 *  name="authorization",
 *  in="header",
 *  scheme="bearer",
 *  bearerFormat="JWT",
 *  securityScheme="bearerAuth"
 * )
 */

/**
 *  @OA\Response(
 *    response="ClientError",
 *    description="Client error response",
 *    @OA\JsonContent(
 *      oneOf={
 *        @OA\Schema(ref="#/components/schemas/Error"),
 *        @OA\Schema(ref="#/components/schemas/Details"),
 *      },
 *    ),
 *  )
 *  @OA\Response(
 *    response="ServerError",
 *    description="Server error response",
 *    @OA\JsonContent(
 *      oneOf={
 *        @OA\Schema(ref="#/components/schemas/Error"),
 *        @OA\Schema(ref="#/components/schemas/Details"),
 *      },
 *    ),
 *  )
 *  @OA\Response(
 *    response="400",
 *    description="Bad request",
 *    @OA\JsonContent(
 *      oneOf={
 *        @OA\Schema(ref="#/components/schemas/Error"),
 *        @OA\Schema(ref="#/components/schemas/Details"),
 *      },
 *    ),
 *  )
 *  @OA\Response(
 *    response="401",
 *    description="Unauthorized",
 *    @OA\JsonContent(
 *      oneOf={
 *        @OA\Schema(ref="#/components/schemas/Error"),
 *        @OA\Schema(ref="#/components/schemas/Details"),
 *      },
 *    ),
 *  )
 *  @OA\Response(
 *    response="403",
 *    description="Forbidden",
 *    @OA\JsonContent(
 *      oneOf={
 *        @OA\Schema(ref="#/components/schemas/Error"),
 *        @OA\Schema(ref="#/components/schemas/Details"),
 *      },
 *    ),
 *  )
 *  @OA\Response(
 *    response="404",
 *    description="Not found",
 *    @OA\JsonContent(
 *      oneOf={
 *        @OA\Schema(ref="#/components/schemas/Error"),
 *        @OA\Schema(ref="#/components/schemas/Details"),
 *      },
 *    ),
 *  )
 *  @OA\Response(
 *    response="405",
 *    description="Method not allowed",
 *    @OA\JsonContent(
 *      oneOf={
 *        @OA\Schema(ref="#/components/schemas/Error"),
 *        @OA\Schema(ref="#/components/schemas/Details"),
 *      },
 *    ),
 *  )
 *  @OA\Response(
 *    response="500",
 *    description="Internal server error",
 *    @OA\JsonContent(
 *      oneOf={
 *        @OA\Schema(ref="#/components/schemas/Error"),
 *        @OA\Schema(ref="#/components/schemas/Details"),
 *      },
 *    ),
 *  )
 */

/**
 * @OA\Schema(
 *  schema="Details",
 *  title="Error details",
 * 	@OA\Property(
 * 		property="title",
 * 		type="string"
 * 	),
 * 	@OA\Property(
 * 		property="description",
 * 		type="string"
 * 	),
 * 	@OA\Property(
 * 		property="message",
 * 		type="string"
 * 	),
 * 	@OA\Property(
 * 		property="type",
 * 		type="string"
 * 	),
 * 	@OA\Property(
 * 		property="code",
 * 		type="string"
 * 	),
 * 	@OA\Property(
 * 		property="file",
 * 		type="string"
 * 	),
 * 	@OA\Property(
 * 		property="line",
 * 		type="string"
 * 	),
 * 	@OA\Property(
 * 		property="trace",
 * 		type="string"
 * 	)
 * )
 * @OA\Schema(
 *  schema="Error",
 *  title="Error",
 * 	@OA\Property(
 * 		property="title",
 * 		type="string"
 * 	),
 * 	@OA\Property(
 * 		property="description",
 * 		type="string"
 * 	),
 * 	@OA\Property(
 * 		property="message",
 * 		type="string"
 * 	)
 * )
 */