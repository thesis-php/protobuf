# Included by tests/conformance/Dockerfile right after project(protobuf). protobuf links the runner with the target
# jsoncpp_lib if it exists and fetches jsoncpp otherwise, and jsoncpp makes that target a shared library: a static
# binary cannot be linked with it. So jsoncpp is added here as a static library only, under the name protobuf expects.

set(JSONCPP_WITH_TESTS OFF CACHE BOOL "" FORCE)
set(JSONCPP_WITH_POST_BUILD_UNITTEST OFF CACHE BOOL "" FORCE)
set(BUILD_SHARED_LIBS OFF CACHE BOOL "" FORCE)
set(BUILD_OBJECT_LIBS OFF CACHE BOOL "" FORCE)

add_subdirectory(/src/jsoncpp ${CMAKE_BINARY_DIR}/_deps/jsoncpp-build EXCLUDE_FROM_ALL)
add_library(jsoncpp_lib ALIAS jsoncpp_static)
